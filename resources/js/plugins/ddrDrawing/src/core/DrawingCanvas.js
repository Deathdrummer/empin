/**
 * Основной класс для управления холстом рисования
 */
class DrawingCanvas {
	constructor(containerId) {
		this.containerId = containerId
		this.container = null
		this.graph = null
		this.paper = null
		this.initialized = false
	}

	/**
	 * Инициализация холста
	 */
	init() {
		if (this.initialized) return

		console.log('Initializing DrawingCanvas')
		
		// Проверяем JointJS
		if (!window.joint) {
			throw new Error('JointJS library not found')
		}
		
		// Находим контейнер
		this.container = document.querySelector(this.containerId)
		if (!this.container) {
			throw new Error(`Canvas container ${this.containerId} not found`)
		}
		
		this._createGraph()
		this._createPaper()
		this._updatePaperSize()
		
		this.initialized = true
		console.log('DrawingCanvas initialized')
	}

	/**
	 * Создание графа
	 */
	_createGraph() {
		this.graph = new window.joint.dia.Graph()
	}

	/**
	 * Создание paper
	 */
	_createPaper() {
		this.paper = new window.joint.dia.Paper({
			el: this.container,
			model: this.graph,
			width: '100%',
			height: '100%',
			gridSize: 10,
			drawGrid: {
				name: 'dot',
				args: {
					color: '#cccccc',
					thickness: 1
				}
			},
			background: {
				color: '#fdfdfd'
			}
		})
	}

	/**
	 * Обновление размеров paper
	 */
	updatePaperSize() {
		if (!this.paper) return
		
		const container = this.paper.el
		if (!container || !container.offsetParent) {
			setTimeout(() => this.updatePaperSize(), 100)
			return
		}
		
		const containerWidth = container.offsetWidth
		const containerHeight = container.offsetHeight
		
		console.log('Updating paper size:', containerWidth, 'x', containerHeight)
		
		this.paper.setDimensions(containerWidth, containerHeight)
		this.paper.render()
	}

	/**
	 * Приватный метод для немедленного обновления размеров
	 */
	_updatePaperSize() {
		setTimeout(() => this.updatePaperSize(), 50)
	}

	/**
	 * Получить граф
	 */
	getGraph() {
		return this.graph
	}

	/**
	 * Получить paper
	 */
	getPaper() {
		return this.paper
	}

	/**
	 * Установить масштаб
	 */
	setScale(scale) {
		if (this.paper) {
			this.paper.scale(scale, scale)
		}
	}

	/**
	 * Получить текущий масштаб
	 */
	getScale() {
		return this.paper ? this.paper.scale() : { sx: 1, sy: 1 }
	}

	/**
	 * Установить трансляцию
	 */
	setTranslation(tx, ty) {
		if (this.paper) {
			this.paper.translate(tx, ty)
		}
	}

	/**
	 * Получить текущую трансформацию
	 */
	getMatrix() {
		return this.paper ? this.paper.matrix() : null
	}

	/**
	 * Преобразовать координаты клиента в локальные
	 */
	clientToLocalPoint(clientX, clientY) {
		return this.paper ? this.paper.clientToLocalPoint(clientX, clientY) : { x: 0, y: 0 }
	}
}

export default DrawingCanvas