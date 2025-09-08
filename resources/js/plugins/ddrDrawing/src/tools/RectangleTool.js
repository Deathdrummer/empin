import BaseTool from './BaseTool.js'

/**
 * Инструмент создания прямоугольников
 */
class RectangleTool extends BaseTool {
	constructor(name, canvas, eventManager) {
		super(name, canvas, eventManager)
		this.gridSize = 10
	}

	/**
	 * Активация инструмента
	 */
	onActivate() {
		// Меняем курсор на перекрестие
		this.getPaper().el.style.cursor = 'crosshair'
	}

	/**
	 * Деактивация инструмента
	 */
	onDeactivate() {
		// Сбрасываем курсор
		this.getPaper().el.style.cursor = 'default'
	}

	/**
	 * Обработка клика по холсту
	 */
	onCanvasClick(point, event) {
		console.log('Creating rectangle at:', point)
		this.createRectangle(point.x, point.y)
		
		// Автоматически переключаемся на инструмент выделения
		this.eventManager.emit('tool-auto-switch', 'select')
	}

	/**
	 * Создание прямоугольника
	 */
	createRectangle(x, y) {
		// Привязываем к сетке
		const snapX = Math.round(x / this.gridSize) * this.gridSize
		const snapY = Math.round(y / this.gridSize) * this.gridSize
		
		const rect = new window.joint.shapes.standard.Rectangle({
			position: { x: snapX - 10, y: snapY - 10 },
			size: { width: 20, height: 20 },
			attrs: {
				body: {
					fill: '#ffffff',
					stroke: '#cfcccc',
					strokeWidth: 1
				}
			}
		})
		
		this.getGraph().addCell(rect)
		console.log('Rectangle created at grid position:', snapX, snapY)
		
		return rect
	}

	/**
	 * Установка размера сетки
	 */
	setGridSize(size) {
		this.gridSize = size
	}

	/**
	 * Получение размера сетки
	 */
	getGridSize() {
		return this.gridSize
	}
}

export default RectangleTool