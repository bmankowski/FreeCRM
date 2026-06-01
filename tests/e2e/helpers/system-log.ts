/**
 * FreeCRM E2E — system.log watcher for module crawler
 *
 * Matches Yii FileTarget lines: YYYY-MM-DD HH:MM:SS.micro [level] - message
 * Also matches legacy PHP error_log lines (fatals that bypass the error handler).
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import fs from 'fs';
import path from 'path';

export type LogLevel = 'errors' | 'warnings' | 'all';

const YII_LOG_LINE = /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d+ \[(error|warning|info|trace|profile)\]/;

const LEVEL_PATTERNS: Record<LogLevel, RegExp> = {
	errors: /\[error\]|PHP Fatal error|PHP Parse error|Uncaught/i,
	warnings:
		/\[error\]|\[warning\]|PHP Fatal error|PHP Parse error|Uncaught|PHP Warning|PHP Error/i,
	all: /\[error\]|\[warning\]|\[info\]|\[trace\]|\[profile\]|PHP Fatal error|PHP Parse error|Uncaught|PHP Warning|PHP Error|PHP Deprecated|PHP Notice/i,
};

const STACK_CONTINUATION = /^(Stack trace:|#\d+ |  thrown in |  #\d+ )/;

function isStackContinuation(line: string): boolean {
	return STACK_CONTINUATION.test(line);
}

function isLogHeader(line: string, level: LogLevel): boolean {
	if (LEVEL_PATTERNS[level].test(line)) {
		return true;
	}

	const yiiMatch = YII_LOG_LINE.exec(line);
	if (!yiiMatch) {
		return false;
	}

	const yiiLevel = yiiMatch[1];
	if (level === 'errors') {
		return yiiLevel === 'error';
	}
	if (level === 'warnings') {
		return yiiLevel === 'error' || yiiLevel === 'warning';
	}

	return true;
}

export class SystemLogWatcher {
	private offset = 0;

	constructor(private readonly logPath: string) {}

	snapshot(): void {
		this.offset = fs.existsSync(this.logPath) ? fs.statSync(this.logPath).size : 0;
	}

	readNewLines(): string[] {
		if (!fs.existsSync(this.logPath)) {
			return [];
		}

		const fd = fs.openSync(this.logPath, 'r');
		try {
			const size = fs.fstatSync(fd).size;
			if (size <= this.offset) {
				return [];
			}

			const length = size - this.offset;
			const buffer = Buffer.alloc(length);
			fs.readSync(fd, buffer, 0, length, this.offset);
			this.offset = size;
			return buffer.toString('utf8').split('\n').filter(Boolean);
		} finally {
			fs.closeSync(fd);
		}
	}

	filterByLevel(lines: string[], level: LogLevel): string[] {
		const result: string[] = [];

		for (let i = 0; i < lines.length; i++) {
			const line = lines[i];
			if (!isLogHeader(line, level)) {
				continue;
			}

			result.push(line);
			for (let j = i + 1; j < lines.length && isStackContinuation(lines[j]); j++) {
				result.push(lines[j]);
				i = j;
			}
		}

		return result;
	}
}

export function resolveLogPath(customPath?: string): string {
	if (customPath) {
		return path.isAbsolute(customPath)
			? customPath
			: path.resolve(process.cwd(), customPath);
	}

	return path.resolve(__dirname, '../../../cache/logs/system.log');
}

export function parseLogLevel(value: string | undefined): LogLevel {
	if (value === 'errors' || value === 'warnings' || value === 'all') {
		return value;
	}
	return 'warnings';
}
