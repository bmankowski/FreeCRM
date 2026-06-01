/**
 * FreeCRM module crawler — visits every exported module view and watches system.log
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import fs from 'fs';
import path from 'path';
import { chromium } from '@playwright/test';
import { login, getLoginCredentials } from '../helpers/login';
import {
	LogLevel,
	SystemLogWatcher,
	parseLogLevel,
	resolveLogPath,
} from '../helpers/system-log';

interface CrawlUrl {
	module: string;
	view: string;
	path: string;
}

interface CrawlFinding {
	url: string;
	module: string;
	view: string;
	status: number | null;
	logLines: string[];
}

interface CrawlReport {
	startedAt: string;
	finishedAt: string;
	baseUrl: string;
	logLevel: LogLevel;
	logPath: string;
	stopOnFail: boolean;
	total: number;
	visited: number;
	stoppedEarly: boolean;
	httpFailures: number;
	logFindings: number;
	findings: CrawlFinding[];
}

interface CrawlerConfig {
	baseUrl: string;
	logLevel: LogLevel;
	logPath: string;
	urlsFile: string;
	reportFile: string;
	stopOnFail: boolean;
}

function printHelp(): void {
	console.log(`FreeCRM module crawler (module-crawler.ts)

Visits module URLs from a JSON file, logs in via Playwright, and diffs
cache/logs/system.log after each page load.

Usage:
  npx tsx scripts/module-crawler.ts [options]

Options:
  -h, --help              Show this help and exit

  --url URL               CRM base URL (default: https://dev.itconnect.pl)
                          Env: FREECRM_URL

  --log-level LEVEL       Log filter: errors | warnings | all
                          errors   — Fatal, Parse, Uncaught
                          warnings — above + Warning, Error
                          all      — above + Deprecated, Notice
                          Default: warnings
                          Env: FREECRM_LOG_LEVEL

  --log-path PATH         system.log path (default: cache/logs/system.log)
                          Env: FREECRM_LOG_PATH

  --urls-file PATH        JSON URL list from export-crawl-urls.php
                          Default: tests/e2e/.crawl-urls.json

  --report-file PATH      JSON report output path
                          Default: tests/e2e/.crawl-report.json

  --stop-on-fail          Stop after first failure (default)

  --continue-on-fail      Visit all URLs; report all failures at end
                          Env: FREECRM_CONTINUE_ON_FAIL=1

Environment:
  FREECRM_URL             Base URL
  FREECRM_USER            Login username (default: admin)
  FREECRM_PASS            Login password
  FREECRM_LOG_LEVEL       Same as --log-level
  FREECRM_LOG_PATH        Same as --log-path
  FREECRM_CONTINUE_ON_FAIL  Same as --continue-on-fail

Exit codes:
  0  No failures
  1  Failure detected or runtime error

Prefer ./crawl.sh from the repo root — it exports URLs and runs this script
inside the Playwright Docker image. See ./crawl.sh --help for full docs.
`);
}

function parseArgs(argv: string[]): CrawlerConfig {
	if (argv.includes('-h') || argv.includes('--help')) {
		printHelp();
		process.exit(0);
	}

	const config: CrawlerConfig = {
		baseUrl: process.env.FREECRM_URL || 'https://dev.itconnect.pl',
		logLevel: parseLogLevel(process.env.FREECRM_LOG_LEVEL || undefined),
		logPath: resolveLogPath(process.env.FREECRM_LOG_PATH || undefined),
		urlsFile: path.resolve(__dirname, '../.crawl-urls.json'),
		reportFile: path.resolve(__dirname, '../.crawl-report.json'),
		stopOnFail: process.env.FREECRM_CONTINUE_ON_FAIL !== '1',
	};

	for (let i = 2; i < argv.length; i++) {
		const arg = argv[i];
		const next = argv[i + 1];

		switch (arg) {
			case '--continue-on-fail':
				config.stopOnFail = false;
				break;
			case '--stop-on-fail':
				config.stopOnFail = true;
				break;
			case '--url':
				config.baseUrl = next ?? config.baseUrl;
				i++;
				break;
			case '--log-level':
				config.logLevel = parseLogLevel(next);
				i++;
				break;
			case '--log-path':
				config.logPath = resolveLogPath(next);
				i++;
				break;
			case '--urls-file':
				config.urlsFile = path.isAbsolute(next ?? '')
					? (next as string)
					: path.resolve(process.cwd(), next ?? config.urlsFile);
				i++;
				break;
			case '--report-file':
				config.reportFile = path.isAbsolute(next ?? '')
					? (next as string)
					: path.resolve(process.cwd(), next ?? config.reportFile);
				i++;
				break;
		}
	}

	return config;
}

function loadUrls(urlsFile: string): CrawlUrl[] {
	if (!fs.existsSync(urlsFile)) {
		throw new Error(`URLs file not found: ${urlsFile}. Run export-crawl-urls.php first.`);
	}

	const raw = fs.readFileSync(urlsFile, 'utf8');
	const parsed = JSON.parse(raw) as CrawlUrl[];

	if (!Array.isArray(parsed) || parsed.length === 0) {
		throw new Error(`URLs file is empty or invalid: ${urlsFile}`);
	}

	return parsed;
}

function isHttpFailure(status: number | null): boolean {
	if (status === null) {
		return true;
	}
	return status >= 400;
}


function printFindingDetails(finding: CrawlFinding): void {
	console.log('');
	console.log('=== Failure details ===');
	console.log(`URL:    ${finding.url}`);
	console.log(`Module: ${finding.module}`);
	console.log(`View:   ${finding.view}`);
	console.log(`HTTP:   ${finding.status ?? 'navigation failed'}`);
	if (finding.logLines.length > 0) {
		console.log('Log lines:');
		for (const line of finding.logLines) {
			console.log(`  ${line}`);
		}
	}
	console.log('');
}

function writeReport(
	config: CrawlerConfig,
	startedAt: string,
	urls: CrawlUrl[],
	visited: number,
	stoppedEarly: boolean,
	findings: CrawlFinding[],
	httpFailures: number,
): void {
	const finishedAt = new Date().toISOString();
	const report: CrawlReport = {
		startedAt,
		finishedAt,
		baseUrl: config.baseUrl,
		logLevel: config.logLevel,
		logPath: config.logPath,
		stopOnFail: config.stopOnFail,
		total: urls.length,
		visited,
		stoppedEarly,
		httpFailures,
		logFindings: findings.filter((f) => f.logLines.some((line) => !line.startsWith('Navigation error:'))).length,
		findings,
	};

	fs.writeFileSync(config.reportFile, JSON.stringify(report, null, 2));

	console.log('=== Crawl summary ===');
	console.log(`Visited:       ${report.visited} / ${report.total}`);
	if (report.stoppedEarly) {
		console.log('Stopped early: yes (failure encountered)');
	}
	console.log(`HTTP failures: ${report.httpFailures}`);
	console.log(`Log findings:  ${report.logFindings}`);
	console.log(`Report:        ${config.reportFile}`);
}

async function main(): Promise<void> {
	const config = parseArgs(process.argv);
	const urls = loadUrls(config.urlsFile);
	const logWatcher = new SystemLogWatcher(config.logPath);
	const startedAt = new Date().toISOString();
	const findings: CrawlFinding[] = [];
	let httpFailures = 0;
	let visited = 0;
	let stoppedEarly = false;

	console.log(`Base URL:   ${config.baseUrl}`);
	console.log(`Log level:  ${config.logLevel}`);
	console.log(`Log path:   ${config.logPath}`);
	console.log(`URLs file:  ${config.urlsFile}`);
	console.log(`Stop on fail: ${config.stopOnFail ? 'yes' : 'no'}`);
	console.log(`Total URLs: ${urls.length}`);
	console.log('');

	const browser = await chromium.launch();
	const page = await browser.newPage();

	try {
		await login(page, config.baseUrl, getLoginCredentials());

		for (let i = 0; i < urls.length; i++) {
			const entry = urls[i];
			const fullUrl = `${config.baseUrl}/${entry.path.replace(/^\//, '')}`;

			logWatcher.snapshot();

			let status: number | null = null;
			let finding: CrawlFinding | null = null;

			try {
				const response = await page.goto(fullUrl, {
					waitUntil: 'domcontentloaded',
					timeout: 30000,
				});
				status = response?.status() ?? null;
				await page.waitForLoadState('networkidle', { timeout: 15000 }).catch(() => {});
			} catch (error) {
				const message = error instanceof Error ? error.message : String(error);
				finding = {
					url: fullUrl,
					module: entry.module,
					view: entry.view,
					status: null,
					logLines: [`Navigation error: ${message}`],
				};
			}

			if (!finding) {
				const logLines = logWatcher.filterByLevel(logWatcher.readNewLines(), config.logLevel);
				const hasHttpFailure = isHttpFailure(status);
				const hasLogFindings = logLines.length > 0;

				if (hasHttpFailure || hasLogFindings) {
					finding = {
						url: fullUrl,
						module: entry.module,
						view: entry.view,
						status,
						logLines,
					};
				}
			}

			visited = i + 1;
			const label = `${entry.module}/${entry.view}`;

			if (finding) {
				if (isHttpFailure(finding.status)) {
					httpFailures++;
				}
				findings.push(finding);

				const logCount = finding.logLines.filter((line) => !line.startsWith('Navigation error:')).length;
				console.log(
					`[${visited}/${urls.length}] FAIL ${finding.status ?? '?'} ${label} (${logCount} log lines, HTTP ${finding.status ?? '?'})`,
				);
				printFindingDetails(finding);

				if (config.stopOnFail) {
					stoppedEarly = true;
					break;
				}
			} else {
				console.log(`[${visited}/${urls.length}] ${status ?? '?'} ${label} OK`);
			}
		}
	} finally {
		await browser.close();
	}

	writeReport(config, startedAt, urls, visited, stoppedEarly, findings, httpFailures);

	const hasFailure = findings.length > 0;
	process.exit(hasFailure ? 1 : 0);
}

main().catch((error) => {
	console.error(error instanceof Error ? error.message : error);
	process.exit(1);
});
