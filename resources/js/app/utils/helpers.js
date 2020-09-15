export function copyElById (id) {
	let elToCopy = document.getElementById(id)
	elToCopy.select()

	try {
	  var successful = document.execCommand('copy')
	  if (successful) {
	  	this.$root.$snackbar('Copied to clipboard')
	  } else {
	    this.$root.$snackbar('Text could not be copied')
	  }
	} catch (err) {
	  this.$root.$snackbar('Text could not be copied')
	}
}