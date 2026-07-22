import fs from 'node:fs';
import vm from 'node:vm';

function assert(condition, message) {
	if (!condition) throw new Error(message);
}

function classList() {
	const values = new Set();
	return {
		add: value => values.add(value),
		remove: value => values.delete(value),
		contains: value => values.has(value),
		toString: () => Array.from(values).join(' '),
	};
}

const listeners = {};
const stored = new Map();
const select = {
	value: 'light',
	dataset: {},
	addEventListener(name, callback) { listeners[name] = callback; },
};
const document = {
	readyState: 'complete',
	body: { classList: classList(), dataset: {} },
	documentElement: { classList: classList() },
	querySelector(selector) {
		return selector === '[data-adam-theme-switcher]' ? null : null;
	},
	querySelectorAll(selector) {
		return selector === '[data-adam-theme-select]' ? [select] : [];
	},
	addEventListener() {},
	removeEventListener() {},
	dispatchEvent() {},
};
const window = {
	document,
	localStorage: {
		getItem: key => stored.has(key) ? stored.get(key) : null,
		setItem: (key, value) => stored.set(key, value),
		removeItem: key => stored.delete(key),
	},
	matchMedia: () => ({ matches: false, addEventListener() {} }),
	CustomEvent: class { constructor(name, options) { this.type = name; this.detail = options.detail; } },
	URLSearchParams,
};
window.window = window;

Object.defineProperty(window, 'adamUIConfig', {
	configurable: false,
	writable: true,
	value: {
		mode: 'light', fallbackMode: 'light', modes: ['light', 'dark', 'system'],
		resolvedThemes: ['light', 'dark'], classMap: { light: 'adam-theme-light', dark: 'adam-theme-dark' },
		systemMode: 'system', systemQuery: '(prefers-color-scheme: dark)', systemDark: 'dark', systemFallback: 'light',
		storage: { adapter: 'localStorage', key: 'adam-theme' },
	},
});
Object.defineProperty(window, 'adamUIAssetConfig', { configurable: false, writable: true, value: { components: [] } });

const source = fs.readFileSync(new URL('../assets/js/ui.js', import.meta.url), 'utf8');
vm.runInNewContext(source, { window, document, URLSearchParams });

assert(window.ADAMUI, 'Controller must initialize with WordPress localized globals.');
assert(typeof listeners.change === 'function', 'Theme selector change event must be bound.');

select.value = 'dark';
listeners.change();
assert(document.body.classList.contains('adam-theme-dark'), 'Night selection must update the body class.');
assert(document.body.dataset.adamTheme === 'dark', 'Night selection must update the body data attribute.');
assert(stored.get('adam-theme') === 'dark', 'Night selection must persist in localStorage.');

console.log('PASS: Theme controller runtime contract.');
