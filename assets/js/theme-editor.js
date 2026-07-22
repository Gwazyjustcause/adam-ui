(function ($) {
	'use strict';
	var root = document.querySelector('[data-adam-theme-editor]');
	if (!root) return;
	var preview = root.querySelector('[data-adam-preview]');
	function apply(input, value) {
		if (!input.dataset.adamToken) return;
		value = value || input.value;
		if (input.dataset.unit && String(value).slice(-input.dataset.unit.length) !== input.dataset.unit) value += input.dataset.unit;
		preview.style.setProperty(input.dataset.adamToken, value);
		var output = input.parentNode.querySelector('output'); if (output) output.value = value;
	}
	$('.adam-color-field').wpColorPicker({change:function(e,ui){apply(e.target,ui.color.toString());},clear:function(e){apply(e.target,'transparent');}});
	root.addEventListener('input', function (event) { apply(event.target); });
	root.querySelectorAll('[data-adam-token]').forEach(function(input){ apply(input); });
}(jQuery));
