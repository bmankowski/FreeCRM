<?php

namespace App\Modules\Settings\Github\Models;
use App\Modules\Settings\Github\Models\Issues;



/**
 * Client Model
 * @package YetiForce.Github
 * @license licenses/License.html
 * @author Tomasz Kur <t.kur@yetiforce.com>
 */
class Client
{

	const repository = 'FreeCRM';
	const ownerRepository = 'FreeCRM';
	const url = 'https://api.github.com';
	const timeout = 240;

	private $clientId;
	private $clientToken;
	private $username;

	public function setUsername($name)
	{
		$this->username = $name;
	}

	public function setClientId($id)
	{
		$this->clientId = $id;
	}

	public function setToken($token)
	{
		$this->clientToken = $token;
	}

	public function getAllIssues($numPage, $state, $author = false)
	{
		$data['page'] = $numPage;
		$data['per_page'] = 20;
		$path = '/search/issues';
		$data['q'] = 'user:' . self::ownerRepository . ' repo:' . self::repository . " is:issue is:$state";
		if ($author) {
			$data['q'].=" author:$this->username";
		}
		$issues = $this->doRequest($path, 'GET', $data, '200');
		if ($issues === false) {
			return false;
		}
		$issuesModel = [];
		foreach ($issues->items as $issue) {
			$issuesModel[] = \App\Modules\Settings\Github\Models\Issues::getInstanceFromArray($issue);
		}
		\App\Modules\Settings\Github\Models\Issues::$totalCount = $issues->total_count;
		return $issuesModel;
	}

	public function createIssue($title, $body, array $labels = [])
	{
		$path = '/repos/' . self::ownerRepository . '/' . self::repository . '/issues';
		$data = [
			'title' => $title,
			'body' => $body,
		];
		if ($labels !== []) {
			$data['labels'] = array_values($labels);
		}
		return $this->doRequest($path, 'POST', json_encode($data), '201');
	}

	public function isAuthorized()
	{
		if ((empty($this->clientId) || empty($this->clientToken))) {
			return false;
		}
		return true;
	}

	static function getInstance()
	{
		$instance = new self();
		$row = (new \App\Db\Query())
			->select(['client_id', 'token', 'username'])
			->from('u_#__github')
			->createCommand()->queryOne();
		if (!empty($row)) {
			$instance->setClientId($row['client_id']);
			$instance->setToken(base64_decode($row['token']));
			$instance->setUsername($row['username']);
		}
		return $instance;
	}

	public function saveKeys()
	{
		$clientToken = base64_encode($this->clientToken);
		$params = ['client_id' => $this->clientId,
			'token' => $clientToken,
			'username' => $this->username];
		return \App\Db\Db::getInstance()->createCommand()->update('u_#__github', $params)->execute();
	}

	public function checkToken()
	{
		$data['access_token'] = $this->clientToken;
		$userInfo = $this->doRequest('/user', 'GET', $data, '200');
		if (!(empty($userInfo->login) || empty($this->username))) {
			if ($userInfo->login == $this->username) {
				return true;
			}
		}
		return false;
	}

	private function doRequest($url, $method, $data, $status)
	{
		$url = self::url . $url;
		$curl = curl_init();
		if ($this->isAuthorized()) {
			curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($curl, CURLOPT_USERPWD, "$this->clientId:$this->clientToken");
		}
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'FreeCRM');
		curl_setopt($curl, CURLOPT_TIMEOUT, self::timeout);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		switch ($method) {
			case 'GET':
				curl_setopt($curl, CURLOPT_HTTPGET, true);
				if (count($data))
					$url .= '?' . http_build_query($data);
				break;

			case 'POST':
				curl_setopt($curl, CURLOPT_POST, true);
				if (count($data))
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				break;
		}
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$content = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($code != $status) {
			return false;
		}
		$response = json_decode($content);
		return $response;
	}
}
