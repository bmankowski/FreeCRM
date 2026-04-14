<?php

/**
 * Cron for scheduled import.
 *
 * Pliki będą wrzucane przez automat do katalogu /var/www/yetiforce/modules/Zamowienia/scripts/import/zamowienia/
 * Dla każdego zamówienia będą wrzucane trzy pliki:
 *  - z PDF pobranym z OnePlace o nazwie numerZamowienia.pdf
 *  - z XML pobranym z OnePlace o nazwie numerZamowienia_OnePlace.xml
 *  - z XML w formie YetiForce o nazwie numerZamowienia_YetiForce.xml
 * A następnie dla każdego pliku o masce *YetiForce.xml będzie następowało przetwarzanie pliku YetiForce i załączanie wszystkich 3 plików.
 * @package   App
 *
 * @copyright
 * @license
 * @author    Bartłomiej Mańkowski <bmankowski@itconnect.pl>
 */
require_once('modules/Kandydaci/crons/DocumentParser.php');

namespace App\Modules\Kandydaci\Crons;

/**
 * Import_ScheduledImport_Cron class.
 */
class ScheduledImport extends \App\CronHandler
{

	/**
	 * {@inheritdoc}
	 */
	public function process()
	{
		self::importNewCandidates();
	}


	public static function importNewCandidates()
	{
		self::vecho("Rozpoczynam import nowych kandydatów");
		$directory = "/var/www/import/cv/pending/";

		$jsonFiles = glob($directory . "*.json");
		foreach ($jsonFiles as $jsonFilePath) {
			try {
				self::vecho("Przystępuję do importu nowego kandydata z pliku:$jsonFilePath");
				$jsonFilename = basename($jsonFilePath, ".json");

				// If jsonFilename does not contain underscore, then application number is just filename, probably from "Polec znajomego" form
				if (!strpos($jsonFilename, "_")) {
					$candidateApplicationNumber = $jsonFilename;
					self::vecho("Aplikacja nie ma numeru aplikacji, więc to pewnie polec znajomego: " . $candidateApplicationNumber);
				} else {
					// If jsonFilename contains underscore, then application number is after underscore
					$candidateApplicationNumber = explode("_", $jsonFilename)[1]; //Generowany losowo numer aplikacji
				}
                var_dump($candidateApplicationNumber);

				
				$application = Kandydaci_ScheduledImport_Cron::getApplicationData($directory, $jsonFilePath, $candidateApplicationNumber);

				self::vecho("Sprawdzam czy aplikacja " . $candidateApplicationNumber . " dla kandydata " . $application["candidateName"] . " jest już w bazie danych");

				//Jeśli ta aplikacja została już wprowadzona, to jej pliki zostają skasowane
				if (self::isApplicationInDatabase($candidateApplicationNumber)) {
					self::vecho("Aplikacja " . $candidateApplicationNumber . " jest już w bazie danych jsonFilePath: $jsonFilePath");
					self::deleteFiles($application);
					continue;
				}
				self::vecho("Aplikacji " . $candidateApplicationNumber . " nie ma w bazie danych. Procesuję.");

				$candidate = self::getCandidate($application);
				self::addCommentToCandidate($candidate, $application);
				$candidate->save();
				self::addCVToCandidate($candidate, $application);
				// Candidate must exist to be bound to project
				$candidate->save();
				self::bindCandidateToProject($candidate, $application);
				$candidate->save();
				self::deleteFiles($application);
			} catch (Exception $e) {
				self::sendErrorMail($application, $e);
				self::moveFilesToFailed($application);
				\App\Log::error($e);
			}
		}
		self::vecho("Koniec importu plików");
	}

	public static function importAllCandidatesFromFolder($directory)
	{
		$automatUser = \App\User::getUserModel(\App\User::getUserIdByName("automat"));

		if (empty($directory) || !is_dir($directory)) {
			self::vecho("Katalog $directory nie istnieje");
			return;
		}


		$filesToProcess = glob($directory . "*.pdf");
		self::vecho("Rozpoczynam import nowych kandydatów. Katalog:" . $directory . " Liczba plików do przetworzenia: " . count($filesToProcess));
		foreach ($filesToProcess as $filePath) {
			try {
				self::vecho("Przystępuję do importu nowego kandydata z pliku:$filePath");
				$fileName = basename($filePath, ".pdf");
				// Changing Jan-Kowalski-37460 to an array of strings firstname=Jan lastname=Kowalski applicationNumber=37460
				$parts = explode("-", $fileName);
				// Ensure we have at least 3 parts (firstname, lastname, applicationNumber)
				if (count($parts) >= 3) {
					$candidateData = [
						'firstname' => $parts[0],
						'lastname' => $parts[1],
						'applicationNumber' => $parts[2]
					];
				} else {
					echo "Invalid filename format!";
					return;
				}


				self::vecho("Sprawdzam czy aplikacja " . $candidateData['applicationNumber'] . " jest już w bazie danych");

				//Jeśli ta aplikacja została już wprowadzona, to jej pliki zostają skasowane
				if (self::isApplicationInDatabase($candidateData['applicationNumber'])) {
					self::vecho("Aplikacja " . $candidateData['applicationNumber'] . " jest już w bazie danych: $filePath");
					continue;
				}

				try {
					$pdfContent = substr(LukeMadhanga\DocumentParser::parseFromFile($filePath), 0, 10000);
				} catch (Exception $e) {
					self::vecho("Błąd podczas parsowania pliku" . $e->getMessage());
					$fileContent = "";
				}
//				Extract email from pdfContent
				preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}\b/i', $pdfContent, $matches);
				$email = $matches[0] ?? null;
				/**
				 * @var \App\Modules\Kandydaci\Models\Record $candidate
				 */
				if(empty($email)){
					self::vecho("Nie znaleziono emaila w pliku $filePath");
					continue;
				}
				$candidateId = self::getCandidateIdByNameAndEmail($candidateData['lastname'] . " " . $candidateData['firstname'], $email);
				if (!empty($candidateId)) {
					self::vecho("Kandydat " . $candidateData['lastname'] . " " . $candidateData['firstname'] . " jest już bazie: " . $candidateId);
					$candidate = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, 'Kandydaci');
					self::vecho("Kandydat " . $candidateData['lastname'] . " " . $candidateData['firstname'] . " jest już bazie: " . $candidate->getId());
					//TODO: add automatic translations
					$commentWithMessage = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
					$commentWithMessage->set('assigned_user_id', $automatUser->getId());
					$commentWithMessage->set('related_to', $candidate->getId());
					$message = "Kandydat zaaplikował ponownie podczas TalentDays";
					$commentWithMessage->set('commentcontent', $message);
					$commentWithMessage->save();
				} else {
					self::vecho("Kandydata " . $candidateData['lastname'] . " " . $candidateData['firstname'] . " nie ma w bazie danych. Tworzę nowego kandydata");
					$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Kandydaci');
					$candidate->set("name", $candidateData['lastname'] . " " . $candidateData['firstname']);
					$candidate->set("status_kandydata", "Kandydat");
					$candidate->set("email_prywatny", $email);
					$candidate->set("zrodlo_aplikacji", "TalentDays");
					$candidate->set("is_future_contact_allowed", 1);
					$candidate->set("data_maksymalny_kontakt_rodo", date('Y-m-d', strtotime('+3 years')));
					$candidate->save();
				}
				$candidate->set("application_id", $candidateData['applicationNumber']);
				$candidate->set("test","TaletDays");
				$clean_text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $pdfContent);
				$cvContent = trim($clean_text);
				$candidate->set('tresc_cv', $cvContent);
				self::vecho("Ustawiam relacje");
				$relations = self::prepareRelationsString('Kandydaci', $candidate->getId());
				self::vecho("Zapisuje plik z zyciorysem");
				try {
					$documentRecord = self::saveAndDeleteFile($filePath, "CV", $relations);
				} catch (Exception $e) {
					self::vecho("Błąd podczas zapisywania pliku" . $e->getMessage());
					throw $e;
				}
				self::vecho("Transformuje plik");
				$candidate->transformDocumentToCV($documentRecord);
				self::vecho("Zapisuje kandydata");
				$candidate->save();
			} catch (Exception $e) {
//				self::sendErrorMail($application, $e);
//				self::moveFilesToFailed($application);
				self::vecho("Error: " . $e->getMessage());
				\App\Log::error($e);
			}
		}
		self::vecho("Koniec importu plików");
	}

	private static function sendErrorMail($application, $e)
	{
		//@todo change mailing messages to local
		$content = "Yeti: Problem w imporcie Kandydatów";
		$subject = $content;
		if (!empty($application["candidateApplicationNumber"])) {
			$content .= " for application no " . $application["candidateApplicationNumber"];
			$subject = $content;
		}

		if (!empty($application["jsonFilePath"])) {
			$content .= "<br>";
			$content .= "Problem is with file" . $application["jsonFilePath"];
			$content .= "<br>";
		}
		if (!empty($application["filename"])) {
			$content .= "<br>";
			$content .= "The CV path is " . $application["filename"];
			$content .= "<br>";
		}
		$content .= $e->getMessage();
		$mailStatus = \App\Mailer::addMail([
			'to' => ["bmankowski@gmail.com" => "Bartłomiej Mańkowski"],
			'subject' => $subject,
			'content' => $content,
			'smtp_id' => 2,
		]);
	}

	static function switchFirstAndLastName($name): string
	{
		$nameArray = explode(" ", $name);
		$nameArray = array_reverse($nameArray);
		return implode(" ", $nameArray);
	}

	/**
	 * Validates if string contains exactly two capitalized words and converts to title case.
	 * @param string $fullName The input string to validate and transform
	 * @return string|null Returns transformed name or null if validation fails
	 *
	 * Example usage:
	 * $result = formatFullName("JAN KOWALSKI");  // Returns "Jan Kowalski"
	 * $result = formatFullName("jan kowalski");  // Returns null
	 * $result = formatFullName("JAN");           // Returns null
	 * $result = formatFullName("JAN KOWALSKI NOWAK"); // Returns null
	 */
	static function formatFullName(string $fullName): ?string
	{
//		$fullName  = str_replace('-', ' - ', $fullName);
		// Check if string matches pattern: only capital letters, spaces, exactly two words
		if (!preg_match('/^[A-Z]+\s[A-Z]+$/', trim($fullName))) {
			return $fullName;
		}

		// Convert to title case (first letter capital, rest lowercase)
		return ucwords(strtolower($fullName));
	}

	static function deleteFiles($application)
	{
		$jsonFilePath = $application["jsonFilePath"];
		$filename = $application["filename"];
		if (file_exists($jsonFilePath)) {
			unlink($jsonFilePath);
		}
		if (file_exists($filename)) {
			unlink($filename);
		}
	}

	static function moveFilesToProcessed($application)
	{
		self::vecho("Moving file to processed");
		$directory = "/var/www/import/cv/processed/";
		if (file_exists($application["jsonFilePath"])) {
			$filenameJSON = basename($application["jsonFilePath"]);
			self::vecho("!!!Moving file" . $application["jsonFilePath"] . " to processed " . $application["jsonFilePath"]);
			rename($application["jsonFilePath"], $directory . $filenameJSON);
		}
		if (file_exists($application["filename"])) {
			$filenameAttachment = basename($application["filename"]);
			self::vecho("!!!Moving file" . $application["filename"] . " to processed " . $application["filename"]);
			rename($application["filename"], $directory . $filenameAttachment);
		}
	}

	static function moveFilesToFailed($application)
	{
		self::vecho("Moving file to failed");
		$directory = "/var/www/import/cv/failed/";
		if (file_exists($application["jsonFilePath"])) {
			$filenameJSON = basename($application["jsonFilePath"]);
			self::vecho("!!!Moving file" . $application["jsonFilePath"] . " to failed " . $application["jsonFilePath"]);
			rename($application["jsonFilePath"], $directory . $filenameJSON);
		}
		if (file_exists($application["filename"])) {
			$filenameAttachment = basename($application["filename"]);
			self::vecho("!!!Moving file" . $application["filename"] . " to failed " . $application["filename"]);
			rename($application["filename"], $directory . $filenameAttachment);
		}
	}


	static function vecho($string, $condition = true)
	{
		$verbose = true;
		$logs = true;
		if (!$condition) {
			return;
		}
		if ($verbose) {
			echo($string . "\n");
		}
		if ($logs) {
			\App\Log::warning($string);
		}
	}

	/**
	 * @throws Exception
	 */
	static function getApplicationData(string $directory, string $jsonFilePath, string $candidateApplicationNumber)
	{
		/* File structure:
		{
			"post_id": "10246",
			"project_id": "1420532",
			"job_title": "Backend Developer",
			"full_name": "Testowy 90 EN",
			"email": "test@itconnect.pl",
			"phone_number": "501606752",
			"message": "Heja",
			"available_from": "Od zaraz",
			"preferred_contract_type": "B2B",
			"expected_salary": "12000",
			"attachment_cv": "https:\/\/www.itconnect.pl\/wp-content\/uploads\/jet-form-builder\/e04f49a023af7f2b3a8857dfb05d1ff8\/2025\/07\/Testowe-cv-10.docx",
			"future_recruitment_consent": "tak",
			"__form_id": 8063,
			"__refer": "https:\/\/www.itconnect.pl\/en\/oferta\/backend-developer\/",
			"__is_ajax": true,
			"formtype": "apply",
			"cv_original_path": "\/home\/itconnect\/domains\/itconnect.pl\/public_html\/wp-content\/uploads\/jet-form-builder\/e04f49a023af7f2b3a8857dfb05d1ff8\/2025\/07\/Testowe-cv-10.docx",
			"cv_original_filename": "Testowe-cv-10.docx",
			"cv_saved_filename": "apply_cv_8063_2025-07-30_05-17-53.docx",
			"cv_saved_path": "\/home\/itconnect\/domains\/itconnect.pl\/public_html\/wp-content\/uploads\/cv\/apply_cv_8063_2025-07-30_05-17-53.docx",
		}
		*/


		$jsonData = file_get_contents($jsonFilePath);
		$data = json_decode($jsonData, true);

	
		
		$application["rawJSONData"] = $jsonData;
		$application["candidateApplicationNumber"] = $candidateApplicationNumber;
		$application["jsonFilePath"] = $jsonFilePath;
		$application["directory"] = $directory;
		$application["candidateName"] = $data["name"];
		$application["candidateEmail"] = $data["email"];
		$application["candidateOriginalPhone"] = $data["phone_number"];
		$application["candidateTransformedPhone"] = self::try_to_get_correct_phonenumber($application["candidateOriginalPhone"]);
		$application["filename"] = $directory . $data["cv_saved_filename"];
		$application["projectId"] = $data["project_id"];
		$application["sourceId"] = $data["__form_id"];
		$application["agreeToContact"] = $data["future_recruitment_consent"];
		$application["originalFilename"] = $data["cv_original_filename"];
		$application["candidateOriginalPhone"] = $data["phone_number"];
		$application["availability"] = $data["available_from"];
		$application["financialExpectations"] = $data["expected_salary"];
		$application["message"] = $data["message"];
		$application["preferredContractType"] = $data["preferred_contract_type"];
		$application["expectedSalary"] = $data["expected_salary"];
		$application["futureRecruitmentConsent"] = $data["future_recruitment_consent"];
		$application["jobTitle"] = $data["job_title"];
		$application["candidateTransformedPhone"] = self::try_to_get_correct_phonenumber($application["candidateOriginalPhone"]);

		if (!empty($data["cv-imie-nazwisko-polecajaca"])) {
			$application["isReferredByEmployee"] = true;
			$application["referredByEmployee"] = filter_var($data["cv-imie-nazwisko-polecajaca"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["referredOnPosition"] = filter_var($data["cv-nazwa-stanowiska"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["referredByEmail"] = filter_var($data["cv-email-polecajaca"], FILTER_SANITIZE_EMAIL);
		} else {
			$application["isReferredByEmployee"] = false;
		}
		return $application;
	}

	/**
	 * @throws Exception
	 */
	static function getApplicationDataOld(string $directory, string $jsonFilePath, string $candidateApplicationNumber)
	{
		$jsonData = file_get_contents($jsonFilePath);
		$data = json_decode($jsonData, true);



		$entries = $data["entries"];
		if (empty($entries["cv-imie-nazwisko"]) && empty($entries["cv-imie-nazwisko-en"])) {
			throw new Exception("No name in application");
		}

		$application["rawJSONData"] = $jsonData;
		$application["candidateApplicationNumber"] = $candidateApplicationNumber;
		$application["jsonFilePath"] = $jsonFilePath;
		$application["directory"] = $directory;
		$application["candidateName"] = html_entity_decode(filter_var($entries["cv-imie-nazwisko"], FILTER_SANITIZE_FULL_SPECIAL_CHARS), ENT_QUOTES | ENT_HTML401, 'UTF-8');
		if (!empty($entries["cv-imie-nazwisko-polecajaca"])) {
			$application["isReferredByEmployee"] = true;
			$application["referredByEmployee"] = filter_var($entries["cv-imie-nazwisko-polecajaca"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["referredOnPosition"] = filter_var($entries["cv-nazwa-stanowiska"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["referredByEmail"] = filter_var($entries["cv-email-polecajaca"], FILTER_SANITIZE_EMAIL);
		} else {
			$application["isReferredByEmployee"] = false;
		}
		if (!empty($application["candidateName"])) {
			$application["candidateName"] = self::formatFullName(self::switchFirstAndLastName($application["candidateName"]));

			$application["candidateEmail"] = filter_var($entries["cv-email"], FILTER_SANITIZE_EMAIL);
			$application["availability"] = filter_var($entries["cv-od-kiedy"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["financialExpectations"] = filter_var($entries["cv-oczekiwania"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["message"] = filter_var($entries["cv-zostaw-wiadomosc"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if (empty($application["message"])) {
				$application["message"] = filter_var($entries["cv-wiadomosc"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$application["projectId"] = filter_var($entries["cv-id-projektu"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["sourceId"] = filter_var($entries["cv-source-id"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["agreeToContact"] = filter_var($entries["cv-zgoda"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["originalFilename"] = filter_var($data["file_uploads"]["cv-zalacz-cv"][0]["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["filename"] = filter_var(basename($data["file_uploads"]["cv-zalacz-cv"][0]["file"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if (empty($application["filename"])) {
				$application["filename"] = filter_var(basename($data["file_uploads"]["cv-zalacz-cv"][0]["url"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$application["candidateOriginalPhone"] = filter_var($entries["cv-numer-telefonu"], FILTER_SANITIZE_NUMBER_INT); //The FILTER_SANITIZE_NUMBER_INT filter removes all illegal characters from a number. This filter allows digits and . + -
			if (empty($application["candidateOriginalPhone"])) {
				$application["candidateOriginalPhone"] = filter_var($entries["numer-telefonu"], FILTER_SANITIZE_NUMBER_INT); //The FILTER_SANITIZE_NUMBER_INT filter removes all illegal characters from a number. This filter allows digits and . + -
			}
		} else {
			// Use html_entity_decode to convert it to the desired format
			$application["candidateName"] = html_entity_decode(filter_var($entries["cv-imie-nazwisko-en"], FILTER_SANITIZE_FULL_SPECIAL_CHARS), ENT_QUOTES | ENT_HTML401, 'UTF-8');
			if (!empty($application["candidateName"])) {
				//@todo add error handling
				throw new Exception("No name in application");
			}
			$application["candidateName"] = self::switchFirstAndLastName($application["candidateName"]);
			$application["candidateEmail"] = filter_var($entries["cv-email-en"], FILTER_SANITIZE_EMAIL);
			$application["availability"] = filter_var($entries["cv-od-kiedy-en"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["financialExpectations"] = filter_var($entries["cv-oczekiwania-en"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["message"] = filter_var($entries["cv-zostaw-wiadomosc-en"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if (empty($application["message"])) {
				$application["message"] = filter_var($entries["cv-wiadomosc-en"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$application["projectId"] = filter_var($entries["cv-id-projektu"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["sourceId"] = filter_var($entries["cv-source-id"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["agreeToContact"] = filter_var($entries["cv-zgoda"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["originalFilename"] = filter_var($data["file_uploads"]["cv-zalacz-cv-en"][0]["name"], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			$application["filename"] = filter_var(basename($data["file_uploads"]["cv-zalacz-cv-en"][0]["file"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			if (empty($application["filename"])) {
				$application["filename"] = filter_var(basename($data["file_uploads"]["cv-zalacz-cv-en"][0]["url"]), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
			}
			$application["candidateOriginalPhone"] = filter_var($entries["cv-numer-telefonu-en"], FILTER_SANITIZE_NUMBER_INT); //The FILTER_SANITIZE_NUMBER_INT filter removes all illegal characters from a number. This filter allows digits and . + -
			if (empty($application["candidateOriginalPhone"])) {
				$application["candidateOriginalPhone"] = filter_var($entries["numer-telefonu-en"], FILTER_SANITIZE_NUMBER_INT); //The FILTER_SANITIZE_NUMBER_INT filter removes all illegal characters from a number. This filter allows digits and . + -
			}
		}
		if (empty($application["originalFilename"])) {
			$application["originalFilename"] = $application["filename"];
		}
		$application["filename"] = $directory . $application["filename"];
		$application["candidateTransformedPhone"] = self::try_to_get_correct_phonenumber($application["candidateOriginalPhone"]);
		$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
		try {
			$phoneUtil->isValidNumber($phoneUtil->parse($application["candidateTransformedPhone"]));
		} catch (\libphonenumber\NumberParseException $e) {
			self::vecho("Nieprawidłowy numer u kandydata");
			$application["candidateTransformedPhone"] = "";
		}
		return $application;
	}


	static public function bindCandidateToProject(\App\Modules\Kandydaci\Models\Record $candidate, array $application)
	{
		// No project id in application
		if (empty($application["projectId"])) {
			\App\Log::error("No project id in application");
			return 1;
		}

		//Jeśli dany Kandydat identyfikowany przez nazwisko + imię + numer telefonu lub nazwisko + email jest już wprowadzony, to zostaje dowiązany do następnego projektu na jaki aplikuje
		// Sprawdzenie, czy ten kandydat już nie aplikował na ten projekt, jeśli aplikował to go pomijamy
		// Ma to na celu nie wstawianie komentarzy na temat dołączenia Kandydata do Projektu.
		if (self::hasCandidateAppliedForProject($candidate->getId(), $application["projectId"])) {
			self::vecho("Kandydat " . $application["candidateName"] . " już aplikował na ten projekt, pomijam tę aplikację");
			self::deleteFiles($application);
			return null;
		}
		self::vecho("Kandydat " . $application["candidateName"] . " jeszcze nie aplikował na  projekt " . $application["projectId"]);

		if (self::isProjectActive($application["projectId"])) {
			$relationCandidate2Project = self::prepareRelationsString("ProjektyRekrutacyjne", $application["projectId"]);
			if (!empty($relationCandidate2Project)) {
				self::vecho("Dodaje powiązanie kandydata do projektu");
				$candidate->ext = ['relations' => $relationCandidate2Project];
			}
		}
		return 1;
	}

	/**
	 * @throws Exception
	 */
	static public function addCVToCandidate($candidate, $application)
	{
		$originalFilename = $application["directory"] . basename($application["originalFilename"]);
		$filename = $application["filename"];
		// Copying filename to orignal_filename - it will be deleted by saveAndDeleteFile(...)
		self::vecho("Copying:" . $application["filename"] . " to original filename:" . $originalFilename);

		if ($filename != $originalFilename) {
			copy($filename, $originalFilename);
		}
		self::vecho("Przystępuje do otwierania pliku $originalFilename");
		if (file_exists($originalFilename)) {
			try {
				self::vecho("Parsuję plik $originalFilename");
				$fileContent = substr(LukeMadhanga\DocumentParser::parseFromFile($originalFilename), 0, 10000);
			} catch (Exception $e) {
				self::vecho("Błąd podczas parsowania pliku" . $e->getMessage());
				$fileContent = "";
			}
			self::vecho("Ustawiam treść");
			$clean_text = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $fileContent);
			$cvContent = trim($clean_text);
			$candidate->set('tresc_cv', $cvContent);
			self::vecho("Ustawiam relacje");
			$relations = self::prepareRelationsString('Kandydaci', $candidate->getId());
			self::vecho("Zapisuje plik z zyciorysem");
			try {
				$documentRecord = self::saveAndDeleteFile($originalFilename, "CV", $relations);
			} catch (Exception $e) {
				self::vecho("Błąd podczas zapisywania pliku" . $e->getMessage());
				throw $e;
			}
			self::vecho("Transformuje plik");
			$candidate->transformDocumentToCV($documentRecord);
			self::vecho("Moving file to processed");
			self::moveFilesToProcessed($application);
		} else {
			self::vecho("ERROR: Parsowany plik nie istnieje $originalFilename");
		}
	}

	/**
	 * @throws Exception
	 */
	static public function addCommentToCandidate($candidate, $application)
	{
		$automatUser = \App\User::getUserModel(\App\User::getUserIdByName("automat"));

		// Tworzenie komentarza z informacją o oczekiwaniach Kandydata
		if (!empty($application["message"]) || !empty($application["availability"]) || !empty($application["financialExpectations"])) {

			//TODO: add automatic translations
			$conditions = "Dostępność: " . $application["availability"] . "<br>Oczekiwania finansowe: " . $application["financialExpectations"] . " <br>";
			$commentWithMessage = \App\Modules\Base\Models\Record::getCleanInstance("ModComments");
			$commentWithMessage->set('assigned_user_id', $automatUser->getId());
			$commentWithMessage->set('related_to', $candidate->getId());
			if (empty($application["candidateTransformedPhone"])) {
				//Dodaję numer telefonu do wiadomości od Kandydata
				$messageWithPhone = "Numer kandydata nie został zaakceptowany przez system i wygląda tak:" . $application["candidateOriginalPhone"] . "<br>";
			}
			$commentWithMessage->set('commentcontent', $messageWithPhone . $conditions . "Treść wiadomości:<br>" . $application["message"] . "<BR>" . "Numer aplikacji: " . $application["candidateApplicationNumber"]);
			$commentWithMessage->save();
		}
	}

	/**
	 * @throws Exception
	 */
	static public function getCandidate($application): \App\Modules\Kandydaci\Models\Record
	{
		self::vecho("Procesuję aplikację " . $application["candidateApplicationNumber"] . " dla kandydata " . $application["candidateName"]);
		//Searching for candidate by name and phone
		if (!empty($application["candidateName"]) && !empty($application["candidateTransformedPhone"])) {
			$candidateId = self::getCandidateIdByNameAndPhone($application["candidateName"], $application["candidateTransformedPhone"]);
		}
		if (empty($candidateId) && !empty($application["candidateName"]) && !empty($application["candidateEmail"])) {
			//Searching for candidate by name and email
			$candidateId = self::getCandidateIdByNameAndEmail($application["candidateName"], $application["candidateEmail"]);
		}

		if (empty($candidateId)) {
			try {
				$candidate = self::createNewCandidateFromApplication($application);
			} catch (Exception $e) {
				self::vecho("Error while creating new candidate: " . $e->getMessage());
				throw $e;
			}
		} else {
			self::vecho("Kandydat " . $application["candidateName"] . " o numerze telefonu " . $application["candidateTransformedPhone"] . " jest już bazie");
			$candidate = \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, 'Kandydaci');
			$candidate->set("data_maksymalny_kontakt_rodo", date('Y-m-d', strtotime('+3 years')));
			$candidate->save();
		}
		return $candidate;
	}

	static public function getConsultantByEmail($consultantEmail): ?int
	{
		$konsultantId = (new App\Db\Query())
			->select(['k.konsultanciid'])
			->from(['u_yf_konsultanci k'])
			->innerJoin('vtiger_crmentity e', 'k.konsultanciid = e.crmid')
			->where(['e.deleted' => 0])
			->andWhere([
				'or',
				['k.email_prywatny' => $consultantEmail],
				['k.email_firmowy' => $consultantEmail]
			])
			->scalar();
		if ($konsultantId) {
			return $konsultantId;
		}
		return null;
	}

	static public function getConsultantByName($consultantName): ?int
	{
		$konsultantId = (new App\Db\Query())
			->select(['k.konsultanciid'])
			->from(['u_yf_konsultanci k'])
			->innerJoin('vtiger_crmentity e', 'k.konsultanciid = e.crmid')
			->where(['e.deleted' => 0])
			->andWhere([
				'or',
				['k.name' => $consultantName],
				['k.name' => self::switchFirstAndLastName($consultantName)]
			])
			->scalar();
		if ($konsultantId) {
			return $konsultantId;
		}
		return null;
	}

	/**
	 * @throws Exception
	 */
	static public function createNewCandidateFromApplication($application): \App\Modules\Kandydaci\Models\Record
	{
		if (empty($application["candidateName"])) {
			throw new Exception("Candidate name is empty");
		}
		self::vecho("Creating new candidate " . $application["candidateName"]);
		$candidate = \App\Modules\Base\Models\Record::getCleanInstance('Kandydaci');
		$candidate->set("name", $application["candidateName"]);
		$candidate->set("telefon", $application["candidateTransformedPhone"]);
		$candidate->set("status_kandydata", "Kandydat");
		$candidate->set("email_prywatny", $application["candidateEmail"]);
		$candidate->set("application_id", $application["candidateApplicationNumber"]);
		$candidate->set("zrodlo_aplikacji", self::getSourceName($application["sourceId"]));
		if(!empty($application["agreeToContact"]) && ($application["agreeToContact"]=="Tak" || $application["agreeToContact"]=="Yes" || $application["agreeToContact"]=="true" || $application["agreeToContact"]==1)){
			$isFutureContactAllowed = true;
		} else {
			$isFutureContactAllowed = false;
		}
		if ($isFutureContactAllowed) {
			$candidate->set("data_maksymalny_kontakt_rodo", date('Y-m-d', strtotime('+3 years')));
		} else {
			$candidate->set("data_maksymalny_kontakt_rodo", date('Y-m-d', strtotime('+9 months')));
		}
		$candidate->set("is_future_contact_allowed", $isFutureContactAllowed);
		$candidate->set("application_json_content", $application["rawJSONData"]);
		$candidate->set("is_referred_by_employee", $application["isReferredByEmployee"]);
		if ($application["isReferredByEmployee"]) {
			$candidate->set("referred_by_employee", $application["referredByEmployee"]);
			$referringConsultant = self::getConsultantByEmail($application["referredByEmail"]);
			if (empty($referringConsultant)) {
				$referringConsultant = self::getConsultantByName($application["referredByEmployee"]);
			}
			$candidate->set("polec_znajomego", $referringConsultant);
			$candidate->set("referred_on_position", $application["referredOnPosition"]);
			$candidate->set("referred_by_email", $application["referredByEmail"]);
		}
		$candidate->save();
		$candidateId = $candidate->getId();
		return \App\Modules\Kandydaci\Models\Record::getInstanceById($candidateId, 'Kandydaci');
	}

	static function hasCandidateAppliedForProject(string $candidateId, ?string $projectId)
	{
		if (empty($projectId)) {
			return false;
		}
		$query = (new App\Db\Query())->select([
			'r.crmid'
		])->from('u_yf_projekty_rekrutacyjne_relations_members_entity r')
			->innerJoin('vtiger_crmentity e1', 'e1.crmid = r.crmid')
			->innerJoin('vtiger_crmentity e2', 'e2.crmid = r.relcrmid')
			->where(['e1.deleted' => 0, 'e2.deleted' => 0, "r.crmid" => $projectId, "r.relcrmid" => $candidateId]);
		$row = $query->one();
		if ($row) {
			return true;
		}
		return false;
	}

	static function isProjectActive($projectId): bool
	{
		if (empty($projectId)) {
			return false;
		}
		$query = (new App\Db\Query())->select([
			'p.projektyrekrutacyjneid'
		])->from('u_yf_projektyrekrutacyjne p')
			->innerJoin('vtiger_crmentity e', 'e.crmid = p.projektyrekrutacyjneid')
			->where(['e.deleted' => 0, 'p.projektyrekrutacyjneid' => $projectId])
			->andWhere(["in", 'p.etap_sprzedazy', ["Aktywna", "Oczekiwanie na wybór kandydatów"]]);
		$row = $query->one();
		if ($row) {
			return true;
		}
		return false;
	}

	static function getCandidateIdByNameAndPhone(string $name, string $phone): ?string
	{
		if (empty($phone) || empty($name)) {
			return null;
		}
		$query = (new App\Db\Query())->select([
			'u_yf_kandydaci.kandydaciid'
		])->from('u_yf_kandydaci')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, "telefon" => $phone, "name" => $name]);
		$row = $query->one();
		if ($row) {
			return $row["kandydaciid"];
		}
		return null;
	}

	static function getCandidateIdByName(string $name): ?string
	{
		if (empty($name)) {
			return null;
		}
		$query = (new App\Db\Query())->select([
			'u_yf_kandydaci.kandydaciid'
		])->from('u_yf_kandydaci')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, "name" => $name]);
		$row = $query->one();
		if ($row) {
			return $row["kandydaciid"];
		}
		return null;
	}

	static function getCandidateIdByNameAndEmail(string $name, string $email): ?string
	{
		if (empty($email) || empty($name)) {
			return null;
		}
		$query = (new App\Db\Query())->select([
			'u_yf_kandydaci.kandydaciid'
		])->from('u_yf_kandydaci')
			->innerJoin('u_yf_kandydacicf', 'u_yf_kandydacicf.kandydaciid = u_yf_kandydaci.kandydaciid')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, 'name' => $name])
			->andWhere(['or', ["u_yf_kandydacicf.email_prywatny" => $email], ["u_yf_kandydacicf.email_firmowy" => $email]]);
		$row = $query->one();
		if ($row) {
			return $row["kandydaciid"];
		}
		return null;
	}

	static function getSourceName(string $sourceId)
	{
		$sourceName = (new App\Db\Query())->select([
			'vtiger_zrodlo_aplikacji.zrodlo_aplikacji'
		])->from('vtiger_zrodlo_aplikacji')
			->where(["zrodlo_aplikacjiid" => $sourceId])
			->scalar();
		if ($sourceName) {
			return $sourceName;
		}
		// If source name is not found, return a default value or null
		return "WWW ITC";
	}

	static function try_to_get_correct_phonenumber($phoneNumber): ?string
	{
		$phoneNumber = str_replace("-", "", $phoneNumber);
		$length = strlen($phoneNumber);
		if ($length == 12) {
			if (substr($phoneNumber, 0, 1) == "+") {
				return $phoneNumber; //Correct number
			} else {
				return null;
			}
		}
		if ($length == 13 && substr($phoneNumber, 0, 2) == "00") { //Probably something like 0048501000000
			return '+' . substr($phoneNumber, 2, 11);
		}
		if ($length == 13 && substr($phoneNumber, 0, 1) == "+") { //Probably something like +420501000000 <-Czech Rep.
			return $phoneNumber;
		}
		if ($length == 14 && substr($phoneNumber, 0, 2) == "00") { //Probably something like 00420501000000 <-Czech Rep.
			return '+' . substr($phoneNumber, 2, 12);
		}
		if ($length == 11 && substr($phoneNumber, 0, 2) == "48") { //Probably something like 48501000000
			return '+' . $phoneNumber;
		}
		if ($length == 9) {
			return "+48" . $phoneNumber;
		}
		return null;
	}

	static function isApplicationInDatabase($applicationId): bool
	{
		$row = (new App\Db\Query())->select(['u_yf_kandydaci.application_id'])->from('u_yf_kandydaci')
			->innerJoin('vtiger_crmentity', 'vtiger_crmentity.crmid = u_yf_kandydaci.kandydaciid')
			->where(['vtiger_crmentity.deleted' => 0, 'application_id' => $applicationId])
			->one();
		if ($row) {
			return true;
		}
		return false;
	}

	static function prepareRelationsString(string $moduleName, int $relatedEntityId)
	{
		return [['relatedModule' => "$moduleName", 'reverse' => 'true', 'relatedRecords' => ["$relatedEntityId"], 'param' => ["PPL_APPLIED_BY_WEB"]]];
	}

	static function saveAndDeleteFile($filepath, string $title, array $relations = null)
	{
		$file = App\Fields\File::loadFromPath($filepath);
		$fileName = $file->getName();
		$fileNameLength = \App\TextUtils::getTextLength($fileName);
		$newDocument = \App\Modules\Base\Models\Record::getCleanInstance('Documents');
		if ($fileNameLength > ($maxLength = $newDocument->getField('filename')->get('maximumlength'))) {
			$extLength = 0;
			if (!empty($ext = $file->getExtension())) {
				$ext .= ".{$ext}";
				$extLength = \App\TextUtils::getTextLength($ext);
				$fileName = substr($fileName, 0, $fileNameLength - $extLength);
			}
			$fileName = \App\TextUtils::textTruncate($fileName, $maxLength - $extLength, false) . $ext;
		}
		$fileName = \App\Security\Purifier::decodeHtml(\App\Security\Purifier::purify($fileName));
		$newDocument->set('notes_title', $title);
		$newDocument->set('filename', $fileName);
		$newDocument->set('filestatus', 1);
		$newDocument->set('filelocationtype', 'I');
		$newDocument->file = [
			'name' => $fileName,
			'size' => $file->getSize(),
			'type' => $file->getMimeType(),
			'tmp_name' => $file->getPath(),
			'error' => 0,
		];
		if (isset($relations)) {
			$newDocument->ext = ['relations' => $relations];
		}
		$newDocument->save();   // Tutaj nastepuje usunięcie pliku!
		if (isset($newDocument->ext['attachmentsId'])) {
			//            return array_merge(['crmid' => $newDocument->getId()], $newDocument->ext);
			return $newDocument;
		}
		return false;
	}
}
