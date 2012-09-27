var exps = [
	['email', "^.+\\@(\\[?)[a-zA-Z0-9\\-\\.]+\\.([a-zA-Z]{2,3}|[0-9]{1,3})(\\]?)$", 'Email Address entered is incorrect.'],
	['login', "^([a-zA-Z0-9_]+)$", 'Field \'Username\' contains illegal characters.'],
	['integer',"^([0-9]+)$", 'Field contains illegal characters.']
];

var months = [
	['January', 31],
	["February", 28],
	["March", 31],
	["April", 30],
	["May", 31],
	["June", 30],
	["July", 31],
	["August", 31],
	["September", 30],
	["October", 31],
	["November", 30],
	["December", 31]
];

function getExp(chars) {
	for (var i=0; i<exps.length; i++) if (exps[i][0] == chars) return exps[i][1];
	return false;
}

function checkValue(value, caption, chk, min, max) {
	if (!value) return 'Please enter field \'' + caption +'\'.';
	var ex = getExp(chk);
	ex = ex ? ex : chk;
	if (value.length < min && min > 0) return 'Field \'' + caption + '\' must be at least ' + min + ' characters length.';
	if (value.length > max && max > 0) return 'Field \'' + caption + '\' must be less than or equal ' + max + ' characters length.';
	if (window.RegExp && ex) {
		var r = new RegExp(ex);
		if (!r.test(value)) return 'Field \'' + caption + '\' entered is incorrect or contains illegal characters.';
	}
	return false;
}

function showError(msg) {
	alert(msg);
	return false;
}

function checkForm(form, required) {
	for (var i=0; i<required.length; i++) {
		var result = 0;
		for (var j=0; j<form.elements.length; j++) if (form.elements[j].name == required[i][0] || form.elements[j].name == required[i][0] + '[]') {
			var name = required[i][0];
			var caption = required[i][1];
			var type = required[i][2];
			var chk = required[i][3];
			var min = required[i][4];
			var max = required[i][5];
			var msg = required[i][6];
			var elem = form.elements[j];
			switch (type) {
				case 'list':
					if (elem.value == (chk?chk:0)) return showError(msg ? msg : 'Please select field \'' + caption + '\'.');
				break;
				case 'radio':
					result = result || elem.checked;
				break;
				case 'date':
				case 'datetime':
					var m = eval(form[name + '_month'].value) - 1;
					var y = form[name + '_year'].value;
					var d = months[m][1];
					if (d == 28) d = Math.round((y - 2) / 4) * 4 == y ? 29 : 28;
					if (d < eval(form[name + '_day'].value)) return showError(msg ? msg : "In " + months[m][0] + " there is only " + d + " days."); 
				break;
				case 'prop':
					for (var k = 0; k < form.elements.length; k++) if (form.elements[k].name.substr(0, name.length + 1) == name + '_') if (form.elements[k].checked) result += 1;
					if (result < min && min > 0) return showError(msg ? msg : 'Please select at least ' + min + ' checkbox(s) in \'' + caption + '\'');
					if (result > max && max > 0) return showError(msg ? msg : 'Please select not more than ' + max + ' checkbox(s) in \'' + caption + '\'');
				break;
				case 'listbox':
					for (var k = 0; k < form[name + '[]'].length; k++) if (form[name + '[]'][k].selected) result += 1;
					if (result < min && min > 0) return showError(msg ? msg : 'Please select at least ' + min + ' item(s) in \'' + caption + '\'');
					if (result > max && max > 0) return showError(msg ? msg : 'Please select not more than ' + max + ' item(s) in \'' + caption + '\'');
				break;
				case 'flag':
					if (!form[name + '_checked'].checked) return showError(msg ? msg : 'Please check field \'' + caption + '\'.');
				break;
				default:
					result = checkValue(elem.value, caption, chk, min, max);
					if (result) return showError(msg ? msg : result);
					for (var k = 0; k < form.elements.length; k++) if (form.elements[k].name == name + '_confirmation') if (form.elements[k].value != form.elements[j].value) return showError('\'' + required[i][1] + '\' confirmation failed.');
				break;
			}
		}
		switch (type) {
			case 'radio':
				if (!result) return showError(msg ? msg : 'Please select field \'' + caption + '\'.');
			break;
		}
	}
	return true;
}