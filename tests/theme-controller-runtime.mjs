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
const buttonListeners = {};
const stored = new Map();
const select = {
	value: 'light',
	dataset: {},
	addEventListener(name, callback) { listeners[name] = callback; },
};
const modeButton = {
	dataset: { adamThemeValue: 'dark' },
	attributes: {},
	addEventListener(name, callback) { buttonListeners[name] = callback; },
	setAttribute(name, value) { this.attributes[name] = value; },
};
function backgroundElement(type, backgroundColor, backgroundImage = 'none') {
	return {
		type,
		dataset: {},
		className: type === 'component-card' ? 'adam-example-card' : type === 'component-panel' ? 'adam-content-section' : type === 'component-form' ? 'adam-example-filters' : type === 'component-empty' ? 'adam-comunidade__empty' : type === 'transparent-wrapper' ? 'adam-fields-hero-copy' : type === 'hero-badges' ? 'adam-field-badges--hero' : '',
		computedStyle: { backgroundColor, backgroundImage, padding: '0px', borderWidth: '0px', borderRadius: '0px' },
		matches(selector) {
			if (selector === 'form') return type === 'component-form';
			if (type === 'typography-heading' && selector.includes('h1')) return true;
			if (type === 'panel-summary' && selector === 'summary') return true;
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
const componentPanel = backgroundElement('component-panel', 'rgb(255, 255, 255)');
componentPanel.computedStyle.padding = '30px';
componentPanel.computedStyle.borderWidth = '1px';
const panelSummary = backgroundElement('panel-summary', 'rgb(36, 43, 35)');
panelSummary.parentElement = componentPanel;
const typographyHeading = backgroundElement('typography-heading', 'rgb(255, 255, 255)');
const componentForm = backgroundElement('component-form', 'rgb(255, 255, 255)');
const componentEmpty = backgroundElement('component-empty', 'rgb(248, 250, 252)');
const featureCollection = { className: 'adam-facilities-grid' };
const componentFeature = backgroundElement('component-feature', 'rgb(248, 250, 252)');
componentFeature.parentElement = featureCollection;
componentFeature.computedStyle.padding = '13px';
componentFeature.computedStyle.borderWidth = '1px';
componentFeature.computedStyle.borderRadius = '8px';
const transparentWrapper = backgroundElement('transparent-wrapper', 'rgba(0, 0, 0, 0)');
const heroBadges = backgroundElement('hero-badges', 'rgba(0, 0, 0, 0)');
const backgroundElements = [lightSection, alternateSection, gradientSection, gradientCover, imageCover, coverOverlay, footerContainer, componentCard, componentPanel, panelSummary, typographyHeading, componentForm, componentEmpty, componentFeature, transparentWrapper, heroBadges];
const document = {
	readyState: 'complete',
	body: { classList: classList(), dataset: {} },
	documentElement: { classList: classList() },
	querySelector(selector) {
		return selector === '[data-adam-theme-switcher]' ? null : null;
	},
	querySelectorAll(selector) {
		if (selector === '[data-adam-theme-select]') return [select];
		if (selector === '[data-adam-theme-value]') return [modeButton];
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
assert(typeof buttonListeners.click === 'function', 'Icon Theme Switcher controls must be bound.');

select.value = 'dark';
listeners.change();
assert(document.body.classList.contains('adam-theme-dark'), 'Night selection must update the body class.');
assert(document.body.dataset.adamTheme === 'dark', 'Night selection must update the body data attribute.');
assert(stored.get('adam-theme') === 'dark', 'Night selection must persist in localStorage.');
assert(modeButton.attributes['aria-pressed'] === 'true', 'All Theme Switcher instances must synchronize their active mode.');
assert(lightSection.dataset.adamNightBackground === 'content', 'Very light sections must become Night content surfaces.');
assert(alternateSection.dataset.adamNightBackground === 'alternate', 'Pale alternate sections must retain their semantic hierarchy.');
assert(gradientSection.dataset.adamNightBackground === 'accent', 'Decorative gradients must become solid Night accent surfaces.');
assert(gradientCover.dataset.adamNightBackground === 'accent', 'Image-free gradient Covers must become Night accent surfaces.');
assert(imageCover.dataset.adamNightBackground === 'image', 'Image-backed Covers must remain classified as protected images.');
assert(coverOverlay.dataset.adamNightBackground === 'overlay', 'Cover overlays must be recalculated independently of images.');
assert(footerContainer.dataset.adamNightBackground === 'footer', 'Nested footer containers must use the terminal Night canvas.');
assert(componentCard.dataset.adamNightComponent === 'card', 'ADAM ecosystem cards must receive the shared card contract.');
assert(componentPanel.dataset.adamNightComponent === 'panel', 'Surfaced semantic content sections must receive the shared panel contract.');
assert(componentPanel.dataset.adamNightBackground === 'content', 'Standard content panels must retain the content-surface classification.');
assert(panelSummary.dataset.adamNightBackground === 'transparent', 'Disclosure headings inside content panels must remain part of the panel surface.');
assert(!panelSummary.dataset.adamNightComponent, 'Disclosure headings must never be promoted to standalone components.');
assert(typographyHeading.dataset.adamNightBackground === 'typography', 'Typography elements must receive a non-surface Night classification.');
assert(!typographyHeading.dataset.adamNightComponent, 'Typography elements must never be promoted to components.');
assert(componentForm.dataset.adamNightComponent === 'form', 'ADAM ecosystem filter forms must receive the shared form contract.');
assert(componentEmpty.dataset.adamNightComponent === 'empty', 'BEM empty states must receive the shared empty-state contract.');
assert(componentFeature.dataset.adamNightComponent === 'feature', 'Surfaced children of semantic feature collections must receive the shared feature contract.');
assert(componentFeature.dataset.adamNightBackground === 'content', 'Light feature tiles must still be identified as converted surfaces.');
assert(transparentWrapper.dataset.adamNightBackground === 'transparent', 'Transparent layout wrappers must retain an explicit non-surface classification.');
assert(!transparentWrapper.dataset.adamNightComponent, 'Hero copy wrappers must not be promoted to Hero components.');
assert(heroBadges.dataset.adamNightBackground === 'transparent' && !heroBadges.dataset.adamNightComponent, 'Hero badge positioning wrappers must remain transparent.');

select.value = 'light';
listeners.change();
assert(!document.body.classList.contains('adam-theme-dark'), 'Light selection must disable Night overrides.');
assert(!document.body.classList.contains('adam-theme-light'), 'Light selection must not add an ADAM replacement theme class.');
assert(document.body.dataset.adamTheme === 'light', 'Light pass-through state must remain observable through data attributes.');
assert(stored.get('adam-theme') === 'light', 'Light selection must persist in localStorage.');
assert(!lightSection.dataset.adamNightBackground, 'Light mode must remove Night background classifications.');
assert(!componentCard.dataset.adamNightComponent, 'Light mode must remove Night component classifications.');

console.log('PASS: Theme controller runtime contract.');
