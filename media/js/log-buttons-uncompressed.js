plg_system_hyphenateghsvs = window.plg_system_hyphenateghsvs || {};

(function (document, plg_system_hyphenateghsvs)
{
	'use strict';

	var logButtonsEvents = function logButtonsEvents(callback)
	{
		var logButtonsContainer = document.getElementById("logButtonsContainer");
		var logButtonsOutput    = logButtonsContainer.querySelector(".ajaxOutput");

		logButtonsContainer.addEventListener("click", function (event)
		{
			if (event.target.classList.contains("deleteFile"))
			{
				event.preventDefault();
				logButtonsOutput.innerHTML = "";
				callback({
          			plugin: "PlgSystemHyphenateGhsvsDeleteLogFile",
					ajaxOutput: logButtonsOutput
        			});
      			}
			else if (event.target.classList.contains('showFilePath'))
			{
				event.preventDefault();
				logButtonsOutput.innerHTML = '';
				callback({
					plugin: 'PlgSystemHyphenateGhsvsShowLogFilePath',
					ajaxOutput: logButtonsOutput
				});
      			}
			else if (event.target.classList.contains('showFile'))
			{
        			event.preventDefault();
				logButtonsOutput.innerHTML = '';
        			callback({
          				plugin: 'PlgSystemHyphenateGhsvsShowLogFile',
					ajaxOutput: logButtonsOutput
        			});
      			}
    		});
  	};
	logButtonsEvents(plg_system_hyphenateghsvs.getJson);
})(document, plg_system_hyphenateghsvs);
