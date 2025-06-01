import { parseTemplateVars, evalInScope, processDirectives, compileTemplate, renderStats } from './ddrTemplateParser';
import { LRUCache } from 'lru-cache';
import stableStringify from 'fast-json-stable-stringify';

const templateModules = require.context(
	'../../views/render',			// путь относительно текущего файла после сборки
	true,											// рекурсивно
	/\.tpl$/									 // маска
);
const templatesCache = {};

async function loadTemplate(templateName) {
	if (templatesCache[templateName]) return templatesCache[templateName];

	// путь внутри context должен начинаться с ./
	const relPath = `./${templateName.replace(/\./g, '/')}.tpl`;
	if (!templateModules.keys().includes(relPath)) {
		throw new Error(`Шаблон "${templateName}" не найден по пути "${relPath}"`);
	}

	// raw-loader отдаёт строку в .default или напрямую
	let raw = templateModules(relPath).default ?? templateModules(relPath);
	raw = raw.replace(/\{#[\s\S]*?#\}/g, '');
	templatesCache[templateName] = raw;
	return raw;											 // синхронно, но остаётся Promise<string>
}

const renderCache = new LRUCache({ max: 100, ttl: 1_000 * 60 * 10 });
const compiledTemplates = {};					// готовые функции
const pendingCompiles	 = {};					// промисы компиляции

/**
 * Генерирует уникальный ключ для данных
 */
function generateCacheKey(templateName, data) {
	return `${templateName}:${stableStringify(data)}`;
}





/**
 * Асинхронный рендер с директивами и LRU-кешированием
 */
export async function ddrRender(templateName, data = {}, key = null, testMode = false) {
	const cacheKey = key ?? generateCacheKey(templateName, data);

	// 1️⃣	Есть готовый HTML в кэше	 → сразу возвращаем
	if (renderCache.has(cacheKey)) {
		renderStats.hits++;								 // статистика
		if (testMode) console.log('%c[CACHE HIT]', 'color:green', cacheKey);
		return renderCache.get(cacheKey);
	}

	// 2️⃣	Компиляция уже начата кем-то ещё → ждём её
	if (pendingCompiles[cacheKey]) {
		return pendingCompiles[cacheKey];	 // тот же промис вернётся всем
	}

	// 3️⃣	Мы первые — запускаем компиляцию и кладём промис в pending
	renderStats.misses++;
	if (testMode) console.log('%c[CACHE MISS]', 'color:red', cacheKey);

	pendingCompiles[cacheKey] = (async () => {
		// функция-компилятор
		if (!compiledTemplates[cacheKey]) {
			const raw = await loadTemplate(templateName);
			compiledTemplates[cacheKey] = compileTemplate(raw);
		}

		const html = compiledTemplates[cacheKey](data);
		renderCache.set(cacheKey, html);
		delete pendingCompiles[cacheKey];	 // очистили «в процессе»

		if (testMode) console.log('%c[RENDER]', 'color:cyan', cacheKey);
			return html;
	})();

	return pendingCompiles[cacheKey];
}







export async function ddrRenderWithEvents(tpl, data) {
	const html = await ddrRender(tpl, data);
	const $dom = $(html);

	let lastTouchTime = 0;
	const TOUCH_DELAY = 500;

	$dom.on('touchend click', '[ddr-click]', function(e) {
		if (this.disabled || this.hasAttribute('disabled')) return;

		// TOUCH: фиксация времени и предотвращение фантомного клика
		if (e.type === 'touchend') {
				lastTouchTime = Date.now();
				e.preventDefault(); // важно!
		} else if (e.type === 'click' && Date.now() - lastTouchTime < TOUCH_DELAY) {
				return; // Игнорируем click, если только что был touchend
		}

		// Флаг на элементе для debounce
		if (this._ddrClicked) return;
		this._ddrClicked = true;
		setTimeout(() => { this._ddrClicked = false; }, 50);

		const $el = $(this);
		const expr = $el.attr('ddr-click');
		if (!expr) return;
		const [func, params] = expr.split(':');
		const contextStr = $el.attr('data-ddr-context');
		let contextData = {};
		if (contextStr) {
			try { contextData = JSON.parse(contextStr); } catch (e) { contextData = {}; }
		}
		
		const args = params
			? params.split(',').map(param => {
					const key = param.trim();
					if (contextData.hasOwnProperty(key)) {
							return contextData[key];
					}
					// Если ключа нет — пробуем преобразовать к числу
					// Только если строка действительно является числом
					if (!isNaN(key) && key !== '') {
							return Number(key);
					}
					return key;
			})
			: [];


		if ($.isFunction($[func])) {
			$[func](this, ...args);
		} else {
			console.warn(`Функция $.${func} не найдена`);
		}
	});
	
	
	
	
	
	
	$dom.on('input', '[ddr-input]', function(e) {
		if (this.disabled || this.hasAttribute('disabled')) return;

		const $el = $(this);
		const expr = $el.attr('ddr-input');
		if (!expr) return;
		const [func, params] = expr.split(':');
		const contextStr = $el.attr('data-ddr-context');
		let contextData = {};
		if (contextStr) {
			try { contextData = JSON.parse(contextStr); } catch (e) { contextData = {}; }
		}

		const args = params
			? params.split(',').map(param => {
					const key = param.trim();
					if (contextData.hasOwnProperty(key)) {
						return contextData[key];
					}
					if (!isNaN(key) && key !== '') {
						return Number(key);
					}
					return key;
			})
			: [];

		if ($.isFunction($[func])) {
			$[func](this, ...args);
		} else {
			console.warn(`Функция $.${func} не найдена`);
		}
	});

	

	return $dom; // Уже готовый jQuery-объект с обработчиком на корне!
}












/**
 * ddrMountWithRefs(
 *   target        – DOM | jQuery | string-селектор
 *   tpl           – имя шаблона
 *   data          – объект данных
 *   key?          – кастомный cache-key
 *   jQueryRefs?   – true → refs[name] = $(el)
 * )
 */
export async function ddrMountWithRefs(
	target,
	tpl,
	data = {},
	key = null,
	jQueryRefs = true
) {
	/* ── приводим target к jQuery ── */
	const $target = target instanceof $ ? target : $(target);
	if (!$target.length) throw new Error('Target element not found');

	/* ── рендерим строку ── */
	const html = await ddrRender(tpl, data, key);

	/* ── превращаем в fragment, собираем refs ── */
	const tplEl   = document.createElement('template');
	tplEl.innerHTML = html.trim();
	const frag = tplEl.content.cloneNode(true);

	const refs = {};
	frag.querySelectorAll('[ddr-ref]').forEach(el => {
	const name = el.getAttribute('ddr-ref');
	el.removeAttribute('ddr-ref');

	const refVal = jQueryRefs ? $(el) : el;
	if (refs[name]) {
		(Array.isArray(refs[name])
		? refs[name]
		: (refs[name] = [refs[name]])).push(refVal);
	} else refs[name] = refVal;
	});

	/* ── вставляем fragment c помощью jQuery ── */
	$target.append(frag);          // можно .prepend(frag) при необходимости
	return refs;
}

