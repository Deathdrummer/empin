export const renderStats = { hits: 0, misses: 0 };

export function evalInScope(expression, context) {
	try {
		return new Function(...Object.keys(context), `return ${expression}`)(
			...Object.values(context)
		);
	} catch {
		return false;
	}
}

export function parseTemplateVars(template, data) {
	return template.replace(/{{\s*([\w.]+)\s*}}/g, (_, path) => {
		return path
			.split('.')
			.reduce((acc, key) => (acc == null ? '' : acc[key]), data) ?? '';
	});
}

export function compileTemplate(raw) {
	// удаляем все блоки вида {# … #}
	const stripped = raw.replace(/\{#[\s\S]*?#\}/g, '');
	return async (data) => {
		const doc  = new DOMParser().parseFromString(stripped, 'text/html');
		const root = doc.body.cloneNode(true);
		processDirectives(root, data);
		return root.innerHTML;
	};
}






// Собираем все элементы (и текстовые узлы) в массив
function collectNodes(root) {
    const nodes = [];
    const walker = document.createTreeWalker(
        root,
        NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT,
        null,
        false
    );
    let node;
    while ((node = walker.nextNode())) {
        nodes.push(node);
    }
    return nodes;
}




function processNode(node, data) {
    // ddr-text
    if (node.hasAttribute?.('ddr-text')) {
        const expr = node.getAttribute('ddr-text').trim();
        node.textContent = evalInScope(expr, data) ?? '';
        node.removeAttribute('ddr-text');
    }

    // ddr-html
    if (node.hasAttribute?.('ddr-html')) {
        const expr = node.getAttribute('ddr-html').trim();
        node.innerHTML = evalInScope(expr, data) ?? '';
        node.removeAttribute('ddr-html');
    }

    // ddr-class
    if (node.hasAttribute?.('ddr-class')) {
        let expr = node.getAttribute('ddr-class').trim();
        if (expr.startsWith('{') && expr.endsWith('}')) {
            expr = `(${expr})`;
        }
        const result = evalInScope(expr, data);
        if (typeof result === 'object' && result !== null) {
            Object.entries(result).forEach(([cls, on]) =>
                node.classList.toggle(cls, !!on)
            );
        } else if (typeof result === 'string') {
            result.split(/\s+/).forEach(cls => node.classList.add(cls));
        }
        node.removeAttribute('ddr-class');
    }

    // подстановка {{…}} в атрибутах
    for (const attr of [...(node.attributes || [])]) {
        const substituted = parseTemplateVars(attr.value, data);
        if (substituted !== attr.value) {
            attr.value = substituted;
        }
    }

    // ddr-click, ddr-input
    for (const attr of [...(node.attributes || [])]) {
        if (attr.name === 'ddr-click') {
            const handler = attr.value;
            node.setAttribute('data-ddr-click', handler);

            const [, params] = handler.split(':');
            let contextObj = {};
            if (params) {
                params.split(',').forEach(param => {
                    const parts = param.trim().split('.');
                    const topKey = parts[0];
                    if (data.hasOwnProperty(topKey)) {
                        if (parts.length > 1) {
                            let val = data[topKey];
                            for (let i = 1; i < parts.length; i++) {
                                val = val?.[parts[i]];
                            }
                            contextObj[parts.join('.')] = val;
                        } else {
                            contextObj[topKey] = data[topKey];
                        }
                    }
                });
            }
            try {
                node.setAttribute('data-ddr-context', JSON.stringify(contextObj));
            } catch (e) {
                node.setAttribute('data-ddr-context', '{}');
            }
        }
        if (attr.name === 'ddr-input') {
            const handler = attr.value;
            node.setAttribute('data-ddr-input', handler);
        }
    }
}







export function processDirectives(el, data) {
    const nodes = collectNodes(el);
    let prevIfRemoved = false;

    for (const node of nodes) {
        if (node.nodeType === 1) {
            // ddr-for
            if (node.hasAttribute('ddr-for')) {
			    const expr = node.getAttribute('ddr-for').trim();
			    const [, item, listExpr] = expr.match(/^(\w+)\s+in\s+([\w.]+)$/) || [];
			    const rawListVal = evalInScope(listExpr, data);
			    const listVal = Array.isArray(rawListVal) ? rawListVal : [];

			    if (!item) {
			        console.error('Ошибка ddr-for: не задан item', { expr, data });
			        node._ddr_remove = true;
			        continue;
			    }

			    const parent = node.parentElement;
			    const reference = node.nextSibling;

			    listVal.forEach((val) => {
			        const clone = node.cloneNode(true);
			        clone.removeAttribute('ddr-for');
			        const scoped = { ...data, [item]: val };

			        // ВАЖНО: сначала обрабатываем сам клон (чтобы ddr-click сработал на этом уровне!)
			        processNode(clone, scoped);

			        // Потом — рекурсивно всех детей клона
			        processDirectives(clone, scoped);

			        parent.insertBefore(clone, reference);
			    });

			    node._ddr_remove = true;
			    continue;
			}


            // ddr-if
            if (node.hasAttribute('ddr-if')) {
                const expr = node.getAttribute('ddr-if').trim();
                if (!evalInScope(expr, data)) {
                    prevIfRemoved = true;
                    node._ddr_remove = true;
                    continue;
                }
                prevIfRemoved = false;
                node.removeAttribute('ddr-if');
            }
            // ddr-else
            else if (node.hasAttribute('ddr-else')) {
                if (prevIfRemoved) {
                    prevIfRemoved = false;
                    node.removeAttribute('ddr-else');
                } else {
                    node._ddr_remove = true;
                    continue;
                }
            }

            // ddr-text
            if (node.hasAttribute('ddr-text')) {
                const expr = node.getAttribute('ddr-text').trim();
                node.textContent = evalInScope(expr, data) ?? '';
                node.removeAttribute('ddr-text');
            }

            // ddr-html
            if (node.hasAttribute('ddr-html')) {
                const expr = node.getAttribute('ddr-html').trim();
                node.innerHTML = evalInScope(expr, data) ?? '';
                node.removeAttribute('ddr-html');
            }

            // ddr-class
            if (node.hasAttribute('ddr-class')) {
                let expr = node.getAttribute('ddr-class').trim();
                if (expr.startsWith('{') && expr.endsWith('}')) {
                    expr = `(${expr})`;
                }
                const result = evalInScope(expr, data);
                if (typeof result === 'object' && result !== null) {
                    Object.entries(result).forEach(([cls, on]) =>
                        node.classList.toggle(cls, !!on)
                    );
                } else if (typeof result === 'string') {
                    result.split(/\s+/).forEach(cls => node.classList.add(cls));
                }
                node.removeAttribute('ddr-class');
            }

            // подстановка {{…}} в атрибутах
            for (const attr of [...node.attributes]) {
                const substituted = parseTemplateVars(attr.value, data);
                if (substituted !== attr.value) {
                    attr.value = substituted;
                }
            }

            // ddr-click, ddr-input
            for (const attr of [...node.attributes]) {
                if (attr.name === 'ddr-click') {
                    const handler = attr.value;
                    node.setAttribute('data-ddr-click', handler);

                    const [, params] = handler.split(':');
                    let contextObj = {};
                    if (params) {
                        params.split(',').forEach(param => {
                            const parts = param.trim().split('.');
                            const topKey = parts[0];
                            if (data.hasOwnProperty(topKey)) {
                                if (parts.length > 1) {
                                    let val = data[topKey];
                                    for (let i = 1; i < parts.length; i++) {
                                        val = val?.[parts[i]];
                                    }
                                    contextObj[parts.join('.')] = val;
                                } else {
                                    contextObj[topKey] = data[topKey];
                                }
                            }
                        });
                    }
                    try {
                        node.setAttribute('data-ddr-context', JSON.stringify(contextObj));
                    } catch (e) {
                        node.setAttribute('data-ddr-context', '{}');
                    }
                }
                if (attr.name === 'ddr-input') {
                    const handler = attr.value;
                    node.setAttribute('data-ddr-input', handler);
                }
            }
        } else if (node.nodeType === 3) {
            node.textContent = parseTemplateVars(node.textContent, data);
        }
    }

    // Второй проход: удаляем помеченные
    for (const node of nodes) {
        if (node._ddr_remove) {
            node.parentElement && node.parentElement.removeChild(node);
        }
    }
}






