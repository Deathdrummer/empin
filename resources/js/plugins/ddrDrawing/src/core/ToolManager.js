import logger from './Logger.js'

/**
 * Менеджер инструментов
 */
class ToolManager {
	constructor(canvas, eventManager) {
		this.canvas = canvas
		this.eventManager = eventManager
		this.tools = new Map()
		this.currentTool = null
	}

	/**
	 * Регистрация инструмента
	 */
	registerTool(name, toolClass) {
		const tool = new toolClass(name, this.canvas, this.eventManager)
		this.tools.set(name, tool)
		logger.log(`Tool ${name} registered`)
	}

	/**
	 * Активация инструмента
	 */
	activateTool(name) {
		const tool = this.tools.get(name)
		if (!tool) {
			logger.error(`Tool ${name} not found`)
			return false
		}

		// Деактивируем текущий инструмент
		if (this.currentTool && this.currentTool !== tool) {
			this.currentTool.deactivate()
		}

		// Активируем новый инструмент
		tool.activate()
		this.currentTool = tool

		// Обновляем UI
		this._updateToolbarUI(name)

		logger.log(`Tool ${name} activated`)
		return true
	}

	/**
	 * Получить текущий инструмент
	 */
	getCurrentTool() {
		return this.currentTool
	}

	/**
	 * Получить инструмент по имени
	 */
	getTool(name) {
		return this.tools.get(name)
	}

	/**
	 * Получить все инструменты
	 */
	getAllTools() {
		return Array.from(this.tools.values())
	}

	/**
	 * Обработка клика по холсту
	 */
	handleCanvasClick(point, event) {
		if (this.currentTool) {
			this.currentTool.onCanvasClick(point, event)
		}
	}

	/**
	 * Обработка клика по элементу
	 */
	handleElementClick(element, event) {
		if (this.currentTool) {
			this.currentTool.onElementClick(element, event)
		}
	}

	/**
	 * Инициализация
	 */
	init() {
		// Подписываемся на событие автопереключения инструментов
		this.eventManager.on('tool-auto-switch', (e) => {
			this.activateTool(e.detail)
		})
	}

	/**
	 * Обновление UI панели инструментов
	 */
	_updateToolbarUI(activeTool) {
		// Убираем активный класс со всех кнопок
		document.querySelectorAll('.ddrdrawing__tool-btn').forEach(btn => {
			btn.classList.remove('active')
		})

		// Добавляем активный класс к текущему инструменту
		const activeBtn = document.querySelector(`[data-tool="${activeTool}"]`)
		if (activeBtn) {
			activeBtn.classList.add('active')
		}
	}
}

export default ToolManager