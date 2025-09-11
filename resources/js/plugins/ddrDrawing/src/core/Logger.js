/**
 * Простой логгер для отладки плагина
 */
class Logger {
	constructor(enabled = false) {
		this.enabled = enabled
		this.prefix = '[ddrDrawing]'
	}

	/**
	 * Включить/выключить логирование
	 */
	setEnabled(enabled) {
		this.enabled = enabled
	}

	/**
	 * Обычное сообщение
	 */
	log(...args) {
		if (this.enabled) {
			console.log(this.prefix, ...args)
		}
	}

	/**
	 * Информационное сообщение
	 */
	info(...args) {
		if (this.enabled) {
			console.info(this.prefix, ...args)
		}
	}

	/**
	 * Предупреждение
	 */
	warn(...args) {
		if (this.enabled) {
			console.warn(this.prefix, ...args)
		}
	}

	/**
	 * Ошибка (показывается всегда)
	 */
	error(...args) {
		console.error(this.prefix, ...args)
	}

	/**
	 * Групповое логирование
	 */
	group(label, callback) {
		if (this.enabled) {
			console.group(this.prefix, label)
			callback()
			console.groupEnd()
		}
	}
}

// Глобальный экземпляр логгера
const logger = new Logger(false) // По умолчанию выключен

export default logger