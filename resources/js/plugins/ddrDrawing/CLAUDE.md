# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## ddrDrawing Plugin

Плагин для создания чертежей на основе JointJS 4.1.3 в Laravel-приложении.

### Ключевые файлы
- **Код плагина**: `index.js` 
- **Разметка**: `Z:\empin.loc\resources\views\admin\section\system\system.blade.php` (вкладка systemTab7)
- **Стили**: `Z:\empin.loc\resources\sass\admin.sass` (секция `//-- здесь стили дял плагина ddrDrawing`)


### File Structure
```
resources/
├── js/
│   ├── bootstrap.js          # Global dependencies, JointJS import
│   ├── admin.js             # Admin bundle entry point
│   └── plugins/
│       └── ddrDrawing/      # Drawing plugin
│           ├── index.js     # Main plugin file  
│           └── CLAUDE.md    # This file
├── sass/
│   └── admin.sass           # Admin styles with plugin section. здесь стили дял плагина ddrDrawing
└── views/admin/section/system/
    

### Важные особенности
- **JointJS доступен как `window.joint`** из bootstrap.js - НЕ импортировать заново
- **Отложенная инициализация** для скрытых контейнеров (500ms retry)
- **Контейнер холста**: `#ddrDrawingCanvas`
- **Паттерн плагина**: фабричная функция `ddrDrawing()` возвращает API
- **Инициализация**: `drawingInstance.init()` в blade template (строка 331)

### Архитектура (рефакторенная)
```
src/
├── core/           # Основные классы
│   ├── DrawingCanvas.js    # Управление холстом
│   ├── ToolManager.js      # Менеджер инструментов  
│   └── EventManager.js     # Централизация событий
├── tools/          # Инструменты
│   ├── BaseTool.js         # Базовый класс
│   ├── SelectTool.js       # Инструмент выделения
│   └── RectangleTool.js    # Создание прямоугольников
├── ui/             # UI компоненты  
│   └── ContextMenu.js      # Контекстное меню
└── DrawingPlugin.js        # Главный класс
```

### План развития
См. **ROADMAP.md** - детальный план реализации выносок, соединительных линий, направляющих, дополнительных фигур и других функций.

### Панель инструментов
Кнопки используют `data-tool` атрибуты, обработка через `setTool(toolName)`. Каждый инструмент создания фигуры автоматически переключается на `select` после добавления.
- КМ - контекстное меню