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
function backgroundElement(type, backgroundColor, backgroundImage = 'none') {
	return {
		type,
		dataset: {},
		className: type === 'component-card' ? 'adam-example-card' : type === 'component-form' ? 'adam-example-filters' : type === 'component-empty' ? 'adam-comunidade__empty' : '',
		computedStyle: { backgroundColor, backgroundImage },
		matches(selector) {
			if (selector === 'form') return type === 'component-form';
			if (type === 'overlay') return selector.includes('.wp-block-cover__background');
			if (type === 'alternate') return selector.includes('.is-style-alternate');
			if (type === 'gradient') return selector.includes('-gradient-background');
			if (type === 'image' || type === 'cover-gradient') return selector === '.wp-block-cover';
			return false;
		},
		querySelector(selector) {
			return type === 'image' && selector.includes('.wp-block-cover__image-background') ? {} : null;
		},
		closest() {
			return type === 'footer' ? {} : null;
		},
	};
}
const lightSection = backgroundElement('content', 'rgb(255, 255, 255)');
const alternateSection = backgroundElement('alternate', 'rgb(225, 238, 215)');
const gradientSection = backgroundElement('gradient', 'rgb(240, 248, 235)', 'linear-gradient(rgb(255, 255, 255), rgb(220, 240, 210))');
const gradientCover = backgroundElement('cover-gradient', 'rgb(240, 248, 235)', 'linear-gradient(rgb(255, 255, 255), rgb(220, 240, 210))');
const imageCover = backgroundElement('image', 'rgb(255, 255, 255)', 'url("field.jpg")');
const coverOverlay = backgroundElement('overlay', 'rgba(0, 0, 0, 0.35)');
const footerContainer = backgroundElement('footer', 'rgb(42, 60, 40)');
const componentCard = backgroundElement('component-card', 'rgb(255, 255, 255)');
const componentForm = backgroundElement('component-form', 'rgb(255, 255, 255)');
const componentEmpty = backgroundElement('component-empty', 'rgb(248, 250, 252)');
const backgroundElements = [lightSection, alternateSection, gradientSection, gradientCover, imageCover, coverOverlay, footerContainer, componentCard, componentForm, componentEmpty];
const document = {
	readyState: 'complete',
	body: { classList: classList(), dataset: {} },
	documentElement: { classList: classList() },
	querySelector(selector) {
		return selector === '[data-adam-theme-switcher]' ? null : null;
	},
	querySelectorAll(selector) {
		if (selector === '[data-adam-theme-select]') return [select];
		if (selector === '[data-adam-night-background]') {
			return backgroundElements.filter(element => element.dataset.adamNightBackground);
		}
		if (selector === '[data-adam-night-component]') {
			return backgroundElements.filter(element => element.dataset.adamNightComponent);
		}
		return selector.includes('.wp-block-group') ? backgroundElements : [];
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
	getComputedStyle: element => element.computedStyle,
	CustomEvent: class { constructor(name, options) { this.type = name; this.detail = options.detail; } },
	URLSearchParams,
};
window.window = window;

Object.defineProperty(window, 'adamUIConfig', {
	configurable: false,
	writable: true,
	value: {
		mode: 'light', fallbackMode: 'light', modes: ['light', 'dark', 'system'],
		resolvedThemes: ['light', 'dark'], classMap: { dark: 'adam-theme-dark' },
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
assert(lightSection.dataset.adamNightBackground === 'content', 'Very light sections must become Night content surfaces.');
assert(alternateSection.dataset.adamNightBackground === 'alternate', 'Pale alternate sections must retain their semantic hierarchy.');
assert(gradientSection.dataset.adamNightBackground === 'accent', 'Decorative gradients must become solid Night accent surfaces.');
assert(gradientCover.dataset.adamNightBackground === 'accent', 'Image-free gradient Covers must become Night accent surfaces.');
assert(imageCover.dataset.adamNightBackground === 'image', 'Image-backed Covers must remain classified as protected images.');
assert(coverOverlay.dataset.adamNightBackground === 'overlay', 'Cover overlays must be recalculated independently of images.');
assert(footerContainer.dataset.adamNightBackground === 'footer', 'Nested footer containers must use the terminal Night canvas.');
assert(componentCard.dataset.adamNightComponent === 'card', 'ADAM ecosystem cards must receive the shared card contract.');
assert(componentForm.dataset.adamNightComponent === 'form', 'ADAM ecosystem filter forms must receive the shared form contract.');
assert(componentEmpty.dataset.adamNightComponent === 'empty', 'BEM empty states must receive the shared empty-state contract.');

select.value = 'light';
listeners.change();
assert(!document.body.classList.contains('adam-theme-dark'), 'Light selection must disable Night overrides.');
assert(!document.body.classList.contains('adam-theme-light'), 'Light selection must not add an ADAM replacement theme class.');
assert(document.body.dataset.adamTheme === 'light', 'Light pass-through state must remain observable through data attributes.');
assert(stored.get('adam-theme') === 'light', 'Light selection must persist in localStorage.');
assert(!lightSection.dataset.adamNightBackground, 'Light mode must remove Night background classifications.');
assert(!componentCard.dataset.adamNightComponent, 'Light mode must remove Night component classifications.');

console.log('PASS: Theme controller runtime contract.');
