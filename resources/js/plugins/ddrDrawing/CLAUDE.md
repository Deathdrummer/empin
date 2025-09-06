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

### Архитектура
```javascript
// Проверка доступности JointJS
if (typeof window.joint === 'undefined') {
    console.error('JointJS library not found')
    return
}

// Стандартные фигуры JointJS
const rect = new window.joint.shapes.standard.Rectangle()
const circle = new window.joint.shapes.standard.Ellipse()
```

### Панель инструментов
Кнопки используют `data-tool` атрибуты, обработка через `setTool(toolName)`. Каждый инструмент создания фигуры автоматически переключается на `select` после добавления.