import DrawingPlugin from './src/DrawingPlugin.js'

// Глобальная переменная для экземпляра плагина
let pluginInstance = null

/**
 * Фабричная функция для создания плагина (сохраняем API совместимость)
 */
const ddrDrawing = () => ({
	init: () => {
		console.log('=== ddrDrawing.init() called ===')
		console.log('File path: index.js (main entry point)')
		if (!pluginInstance) {
			pluginInstance = new DrawingPlugin('#ddrDrawingCanvas')
		}
		pluginInstance.init()
	},
	
	setTool: (toolName) => {
		if (pluginInstance) {
			return pluginInstance.setTool(toolName)
		}
		return false
	},
	
	createRectangle: (x, y) => {
		if (pluginInstance) {
			return pluginInstance.createRectangle(x, y)
		}
	},
	
	
	updatePaperSize: () => {
		if (pluginInstance) {
			pluginInstance.updatePaperSize()
		}
	},
	
	undo: () => {
		console.log('Undo - to be implemented')
	},
	
	redo: () => {
		console.log('Redo - to be implemented')
	},
	
	zoomIn: () => {
		if (pluginInstance) {
			pluginInstance.zoomIn()
		}
	},
	
	zoomOut: () => {
		if (pluginInstance) {
			pluginInstance.zoomOut()
		}
	},
	
	zoomToFit: () => {
		if (pluginInstance) {
			pluginInstance.zoomToFit()
		}
	},

	// Новые методы для расширенного API
	getPlugin: () => pluginInstance,
	getCanvas: () => pluginInstance ? pluginInstance.getCanvas() : null,
	getToolManager: () => pluginInstance ? pluginInstance.getToolManager() : null,
	getEventManager: () => pluginInstance ? pluginInstance.getEventManager() : null,
	getContextMenu: () => pluginInstance ? pluginInstance.getContextMenu() : null
})

// Глобальный доступ для обратной совместимости
if (typeof window !== 'undefined') {
	window.ddrDrawing = ddrDrawing
}

export default ddrDrawing