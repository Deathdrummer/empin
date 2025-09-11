import logger from '../core/Logger.js'
import { LineStyles, LineColors, LineStylePatterns } from '../core/LinkMetadata.js'

/**
 * Компонент для выбора стиля линий
 */
class LineStyleSelector {
	constructor(canvas) {
		this.canvas = canvas
		this.currentColor = LineColors.GRAY
		this.currentStyle = LineStyles.SOLID
		this.colorSelect = null
		this.styleSelect = null
	}
	
	/**
	 * Инициализация
	 */
	init() {
		this.createSelectors()
		this.setupEvents()
		logger.log('LineStyleSelector initialized')
	}
	
	/**
	 * Создание выпадающих списков
	 */
	createSelectors() {
		const toolbarSection = document.querySelector('.ddrdrawing__toolbar-section')
		if (!toolbarSection) {
			logger.error('Toolbar section not found for LineStyleSelector')
			return
		}
		
		// Создаем группу для селекторов в стиле остальных групп
		const selectorGroup = document.createElement('div')
		selectorGroup.className = 'ddrdrawing__toolbar-group'
		
		// Контейнер для цвета
		const colorContainer = document.createElement('div')
		colorContainer.className = 'ddrdrawing__select-container'
		
		const colorLabel = document.createElement('span')
		colorLabel.textContent = 'Цвет:'
		colorLabel.className = 'ddrdrawing__select-label'
		
		this.colorSelect = document.createElement('select')
		this.colorSelect.className = 'ddrdrawing__select'
		this.colorSelect.innerHTML = `
			<option value="${LineColors.GRAY}">Серый</option>
			<option value="${LineColors.LIGHT_GRAY}">Светло-серый</option>
			<option value="${LineColors.RED}">Красный</option>
			<option value="${LineColors.BLUE}">Синий</option>
			<option value="${LineColors.GREEN}">Зеленый</option>
			<option value="${LineColors.YELLOW}">Желтый</option>
		`
		
		colorContainer.appendChild(colorLabel)
		colorContainer.appendChild(this.colorSelect)
		
		// Контейнер для стиля
		const styleContainer = document.createElement('div')
		styleContainer.className = 'ddrdrawing__select-container'
		
		const styleLabel = document.createElement('span')
		styleLabel.textContent = 'Линия:'
		styleLabel.className = 'ddrdrawing__select-label'
		
		this.styleSelect = document.createElement('select')
		this.styleSelect.className = 'ddrdrawing__select'
		this.styleSelect.innerHTML = `
			<option value="${LineStyles.SOLID}">Сплошная</option>
			<option value="${LineStyles.DASHED}">Штриховая</option>
			<option value="${LineStyles.DOTTED}">Точечная</option>
			<option value="${LineStyles.DASH_DOT}">Штрих-точка</option>
			<option value="${LineStyles.DASH_DOT_DOT}">Штрих-две точки</option>
			<option value="${LineStyles.LONG_DASH}">Длинный штрих</option>
			<option value="${LineStyles.SHORT_DASH}">Короткий штрих</option>
		`
		
		styleContainer.appendChild(styleLabel)
		styleContainer.appendChild(this.styleSelect)
		
		// Добавляем контейнеры в группу
		selectorGroup.appendChild(colorContainer)
		selectorGroup.appendChild(styleContainer)
		
		// Добавляем группу в тулбар
		toolbarSection.appendChild(selectorGroup)
	}
	
	/**
	 * Настройка событий
	 */
	setupEvents() {
		if (this.colorSelect) {
			this.colorSelect.addEventListener('change', (e) => {
				this.currentColor = e.target.value
				logger.log('Line color changed to:', this.currentColor)
				this.updateSelectedLinks()
			})
		}
		
		if (this.styleSelect) {
			this.styleSelect.addEventListener('change', (e) => {
				this.currentStyle = e.target.value
				logger.log('Line style changed to:', this.currentStyle)
				this.updateSelectedLinks()
			})
		}
	}
	
	/**
	 * Обновление выбранных линков
	 */
	updateSelectedLinks() {
		const graph = this.canvas.getGraph()
		if (!graph) return
		
		// Получаем все выделенные линки
		const links = graph.getLinks()
		links.forEach(link => {
			if (link.get('selected')) {
				// Импортируем LinkMetadata динамически
				import('../core/LinkMetadata.js').then(({ LinkMetadata }) => {
					const metadata = LinkMetadata.fromLink(link)
					if (metadata) {
						metadata.setColor(this.currentColor)
						metadata.setLineStyle(this.currentStyle)
					}
				})
			}
		})
	}
	
	/**
	 * Получить текущие настройки
	 */
	getCurrentSettings() {
		return {
			color: this.currentColor,
			lineStyle: this.currentStyle
		}
	}
	
	/**
	 * Очистка
	 */
	destroy() {
		if (this.colorSelect && this.colorSelect.parentNode) {
			this.colorSelect.parentNode.remove()
		}
	}
}

export default LineStyleSelector