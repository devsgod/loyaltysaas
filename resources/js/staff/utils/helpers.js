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

export function unmask(maskedValue, mask) {
  let defaultTokens = ['#', 'X', 'S', 'A', 'a', '!']
  let unmaskedValue = ''
  let isToken

  for(let i=0; i<maskedValue.length; i++) {
    isToken = false
    for(let j=0; j<defaultTokens.length; j++) {
      if (mask[i] == defaultTokens[j]) {
        isToken = true
      }
    }

    if (isToken){
      unmaskedValue += maskedValue[i]
    }
  }
  return unmaskedValue
}