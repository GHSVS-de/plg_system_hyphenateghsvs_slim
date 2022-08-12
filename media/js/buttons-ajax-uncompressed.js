Joomla = window.Joomla || {};
plg_system_hyphenateghsvs = window.plg_system_hyphenateghsvs || {};

(function (Joomla, plg_system_hyphenateghsvs)
{
  'use strict';

  plg_system_hyphenateghsvs.getJson = function getJson()
	{
    var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
        _ref$plugin = _ref.plugin;
		var plugin = _ref$plugin === void 0 ? '' : _ref$plugin;

		if (plugin)
		{
    	var url = "index.php?option=com_ajax&group=system&plugin=".concat(plugin, "&format=raw");
			var ajaxOutput = _ref.ajaxOutput;
			var Strings = Joomla.getOptions("plg_system_hyphenateghsvs");

			ajaxOutput.innerHTML = "<pre>" + Strings.bePatient + "</pre>";

    	Joomla.request({
				url: url,
      	headers: {
        	'Content-Type': 'application/json'
      	},
      	onSuccess: function onSuccess(response) {
        	try {
          	var json = JSON.parse(response);
          	if (json && json.html)
						{
            	ajaxOutput.innerHTML = "<pre>" + json.html + "</pre>";
          	}
        	} catch (e) {
						ajaxOutput.innerHTML = "<pre>" + Strings.ajaxError
						+ "<br>" + e
						+ "<br>Response:<br>" + htmlEntities(response)
						+ "</pre>";
          	throw new Error(e);
        	}
      	},
      	onError: function onError(xhr) {
        	Joomla.renderMessages({
          	error: [xhr.response]
        	});
      	}
    	});
		};
  };
	function htmlEntities(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}
})(Joomla, plg_system_hyphenateghsvs);
