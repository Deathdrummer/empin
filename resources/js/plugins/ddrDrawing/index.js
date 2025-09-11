import DrawingPlugin from './src/DrawingPlugin.js'
import logger from './src/core/Logger.js'

/**
 * Главный класс плагина рисования
 */
class DdrDrawing {
	constructor(containerId = '#ddrDrawingCanvas') {
		this.containerId = containerId
		this.plugin = null
		this.initialized = false
	}

	/**
	 * Инициализация плагина
	 */
	init() {
		if (this.initialized) {
			logger.warn('Plugin already initialized')
			return
		}

		logger.info('Initializing ddrDrawing plugin')
		
		if (!this.plugin) {
			this.plugin = new DrawingPlugin(this.containerId)
		}
		
		this.plugin.init()
		this.initialized = true
	}

	/**
	 * Установить инструмент
	 */
	setTool(toolName) {
		if (!this.plugin) {
			logger.error('Plugin not initialized')
			return false
		}
		return this.plugin.setTool(toolName)
	}

	/**
	 * Создать прямоугольник
	 */
	createRectangle(x, y) {
		if (!this.plugin) {
			logger.error('Plugin not initialized')
			return
		}
		return this.plugin.createRectangle(x, y)
	}

	/**
	 * Обновить размеры холста
	 */
	updatePaperSize() {
		if (this.plugin) {
			this.plugin.updatePaperSize()
		}
	}

	/**
	 * Отменить действие
	 */
	undo() {
		logger.info('Undo - to be implemented')
	}

	/**
	 * Повторить действие
	 */
	redo() {
		logger.info('Redo - to be implemented')
	}

	/**
	 * Увеличить масштаб
	 */
	zoomIn() {
		if (this.plugin) {
			this.plugin.zoomIn()
		}
	}

	/**
	 * Уменьшить масштаб
	 */
	zoomOut() {
		if (this.plugin) {
			this.plugin.zoomOut()
		}
	}

	/**
	 * Подогнать масштаб
	 */
	zoomToFit() {
		if (this.plugin) {
			this.plugin.zoomToFit()
		}
	}

	// API для расширенного доступа
	getPlugin() { return this.plugin }
	getCanvas() { return this.plugin ? this.plugin.getCanvas() : null }
	getToolManager() { return this.plugin ? this.plugin.getToolManager() : null }
	getEventManager() { return this.plugin ? this.plugin.getEventManager() : null }
	getContextMenu() { return this.plugin ? this.plugin.getContextMenu() : null }

	/**
	 * Включить отладку
	 */
	enableDebug(enabled = true) {
		logger.setEnabled(enabled)
	}

	/**
	 * Уничтожить плагин
	 */
	destroy() {
		if (this.plugin) {
			this.plugin.destroy()
			this.plugin = null
		}
		this.initialized = false
	}
}

// Фабричная функция для обратной совместимости
const ddrDrawing = () => {
	const instance = new DdrDrawing()
	return {
		init: () => instance.init(),
		setTool: (toolName) => instance.setTool(toolName),
		createRectangle: (x, y) => instance.createRectangle(x, y),
		updatePaperSize: () => instance.updatePaperSize(),
		undo: () => instance.undo(),
		redo: () => instance.redo(),
		zoomIn: () => instance.zoomIn(),
		zoomOut: () => instance.zoomOut(),
		zoomToFit: () => instance.zoomToFit(),
		getPlugin: () => instance.getPlugin(),
		getCanvas: () => instance.getCanvas(),
		getToolManager: () => instance.getToolManager(),
		getEventManager: () => instance.getEventManager(),
		getContextMenu: () => instance.getContextMenu(),
		enableDebug: (enabled) => instance.enableDebug(enabled),
		destroy: () => instance.destroy()
	}
}

// Глобальный доступ для обратной совместимости
if (typeof window !== 'undefined') {
	window.ddrDrawing = ddrDrawing
	window.DdrDrawing = DdrDrawing // Прямой доступ к классу
}

export { DdrDrawing, ddrDrawing }
export default ddrDrawing