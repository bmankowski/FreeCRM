/**
 * FreeCRM E2E — system.log watcher for module crawler
 *
 * @package   FreeCRM
 * @copyright FreeCRM (https://freecrm.com)
 * @license   FreeCRM Public License 1.1
 * @author    bmankowski@gmail.com
 */

import fs from 'fs';
import path from 'path';

export type LogLevel = 'errors' | 'warnings' | 'all';

const LEVEL_PATTERNS: Record<LogLevel, RegExp> = {
	errors: /PHP Fatal error|PHP Parse error|Uncaught/i,
	warnings: /PHP Fatal error|PHP Parse error|Uncaught|PHP Warning|PHP Error/i,
	all: /PHP Fatal error|PHP Parse error|Uncaught|PHP Warning|PHP Error|PHP Deprecated|PHP Notice/i,
};

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
		const pattern = LEVEL_PATTERNS[level];
		return lines.filter((line) => pattern.test(line));
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
