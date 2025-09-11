import logger from '../core/Logger.js'

/**
 * Базовый класс для инструментов
 */
class BaseTool {
	constructor(name, canvas, eventManager) {
		this.name = name
		this.canvas = canvas
		this.eventManager = eventManager
		this.active = false
	}

	/**
	 * Активация инструмента
	 */
	activate() {
		if (this.active) return
		
		this.active = true
		this.onActivate()
		logger.log(`Tool ${this.name} activated`)
	}

	/**
	 * Деактивация инструмента
	 */
	deactivate() {
		if (!this.active) return
		
		this.active = false
		this.onDeactivate()
		logger.log(`Tool ${this.name} deactivated`)
	}

	/**
	 * Переопределяемый метод активации
	 */
	onActivate() {
		// Переопределить в наследниках
	}

	/**
	 * Переопределяемый метод деактивации
	 */
	onDeactivate() {
		// Переопределить в наследниках
	}

	/**
	 * Обработка клика по холсту
	 */
	onCanvasClick(point, event) {
		// Переопределить в наследниках
	}

	/**
	 * Обработка клика по элементу
	 */
	onElementClick(element, event) {
		// Переопределить в наследниках
	}

	/**
	 * Получить canvas
	 */
	getCanvas() {
		return this.canvas
	}

	/**
	 * Получить paper
	 */
	getPaper() {
		return this.canvas.getPaper()
	}

	/**
	 * Получить graph
	 */
	getGraph() {
		return this.canvas.getGraph()
	}
}

export default BaseTool