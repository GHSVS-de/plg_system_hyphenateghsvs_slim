/**
 * `Hyphenateghsvs` object constructor.
 */
;function Hyphenateghsvs(selector, context)
{
 this.elementList = (context || document).querySelectorAll(selector);
}

/**
 * Add the specified class to all elements selected by this object.
 */
;Hyphenateghsvs.prototype.addClass = function(cls)
{
 var i, e;
 for (i = 0; i < this.elementList.length ; i++)
	{
  e = this.elementList[i];
		if (e.className)
		{
			e.className += " ";
		}
		e.className += cls;
  // can also use e.classList.add BUT IE >= 10
		//e.classList.add("first","second","third");
 }
}