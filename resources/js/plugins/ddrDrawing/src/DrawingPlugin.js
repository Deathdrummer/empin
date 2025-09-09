import DrawingCanvas from './core/DrawingCanvas.js'
import EventManager from './core/EventManager.js'
import ToolManager from './core/ToolManager.js'
import ContextMenu from './ui/ContextMenu.js'
import SelectTool from './tools/SelectTool.js'
import RectangleTool from './tools/RectangleTool.js'

/**
 * Главный класс плагина рисования
 */
class DrawingPlugin {
	constructor(containerId = '#ddrDrawingCanvas') {
		this.containerId = containerId
		this.canvas = null
		this.eventManager = null
		this.toolManager = null
		this.contextMenu = null
		this.initialized = false
	}

	/**
	 * Инициализация плагина
	 */
	init() {
		if (this.initialized) return

		console.log('=== Initializing DrawingPlugin ===')
		console.log('File path: src/DrawingPlugin.js')

		try {
			// Создаем основные компоненты
			this.canvas = new DrawingCanvas(this.containerId)
			this.eventManager = new EventManager(this.canvas)
			this.toolManager = new ToolManager(this.canvas, this.eventManager)
			this.contextMenu = new ContextMenu(this.canvas)

			// Инициализируем компоненты
			this.canvas.init()
			this.contextMenu.init()
			
			// Устанавливаем связи
			this.eventManager.setToolManager(this.toolManager)
			this.eventManager.setContextMenu(this.contextMenu)
			
			// Регистрируем инструменты
			this.registerTools()
			
			// Инициализируем менеджеры
			this.toolManager.init()
			this.eventManager.init()
			
			// Устанавливаем инструмент по умолчанию
			this.toolManager.activateTool('select')
			
			// Настраиваем обработчики специальных событий
			this.setupEventHandlers()

			this.initialized = true
			console.log('DrawingPlugin initialized successfully')

		} catch (error) {
			console.error('Failed to initialize DrawingPlugin:', error)
			throw error
		}
	}

	/**
	 * Регистрация инструментов
	 */
	registerTools() {
		this.toolManager.registerTool('select', SelectTool)
		this.toolManager.registerTool('rectangle', RectangleTool)
	}


	/**
	 * Настройка обработчиков событий
	 */
	setupEventHandlers() {
		// Обработчики для специальных инструментов
		this.eventManager.on('undo', () => {
			console.log('Undo action')
			// TODO: Реализовать undo
		})

		this.eventManager.on('redo', () => {
			console.log('Redo action')
			// TODO: Реализовать redo
		})

		this.eventManager.on('zoom-in', () => {
			this.zoomIn()
		})

		this.eventManager.on('zoom-out', () => {
			this.zoomOut()
		})

		this.eventManager.on('zoom-fit', () => {
			this.zoomToFit()
		})
	}

	/**
	 * Получить canvas
	 */
	getCanvas() {
		return this.canvas
	}

	/**
	 * Получить менеджер инструментов
	 */
	getToolManager() {
		return this.toolManager
	}

	/**
	 * Получить менеджер событий
	 */
	getEventManager() {
		return this.eventManager
	}

	/**
	 * Получить контекстное меню
	 */
	getContextMenu() {
		return this.contextMenu
	}


	/**
	 * Установить инструмент
	 */
	setTool(toolName) {
		return this.toolManager.activateTool(toolName)
	}

	/**
	 * Увеличение масштаба
	 */
	zoomIn() {
		if (this.canvas) {
			const scale = this.canvas.getScale()
			this.canvas.setScale(scale.sx * 1.2)
		}
	}

	/**
	 * Уменьшение масштаба
	 */
	zoomOut() {
		if (this.canvas) {
			const scale = this.canvas.getScale()
			this.canvas.setScale(scale.sx * 0.8)
		}
	}

	/**
	 * Подгонка по размеру (100%)
	 */
	zoomToFit() {
		if (this.canvas) {
			const paper = this.canvas.getPaper()
			const paperRect = paper.el.getBoundingClientRect()
			const centerX = paperRect.width / 2
			const centerY = paperRect.height / 2
			
			const currentTransform = this.canvas.getMatrix()
			const currentScale = this.canvas.getScale()
			
			const localCenterX = (centerX - currentTransform.e) / currentScale.sx
			const localCenterY = (centerY - currentTransform.f) / currentScale.sy
			
			this.canvas.setScale(1)
			
			const newTx = centerX - localCenterX * 1
			const newTy = centerY - localCenterY * 1
			
			this.canvas.setTranslation(newTx, newTy)
		}
	}

	/**
	 * Обновление размеров paper
	 */
	updatePaperSize() {
		if (this.canvas) {
			this.canvas.updatePaperSize()
		}
	}

	/**
	 * Создание прямоугольника (для обратной совместимости)
	 */
	createRectangle(x, y) {
		const rectangleTool = this.toolManager.getTool('rectangle')
		if (rectangleTool) {
			return rectangleTool.createRectangle(x, y)
		}
	}


	/**
	 * Очистка и уничтожение плагина
	 */
	destroy() {
		if (this.eventManager) {
			this.eventManager.destroy()
		}
		
		if (this.contextMenu) {
			this.contextMenu.destroy()
		}
		
		this.initialized = false
		console.log('DrawingPlugin destroyed')
	}
}

export default DrawingPlugin