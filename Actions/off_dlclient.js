/**
 * 
 */

function getClientOs() {
  if( window.navigator.platform.indexOf("Linux") != -1 ) {
    return "linux";
  }
  if( window.navigator.platform.indexOf("Mac") != -1 ) {
    return "mac";
  }
  if( window.navigator.platform.indexOf("Win") != -1 ) {
    return "win";
  }
  return "unknown";
}

function filterClientByOs() {
  var os = getClientOs();
  if( os == "unknown" ) {
    return unfilterClientByOs();
  }
  var ul = document.getElementById('dl_list');
  for( var i = 0; i < ul.childNodes.length; i++ ) {
    node = ul.childNodes[i];
    if( node.nodeName != 'LI' ) {
      continue;
    }
    if( node.className.indexOf(os) != -1 ) {
      node.style.display = 'block';
    } else {
      node.style.display = 'none';
    }
  }
}

function unfilterClientByOs() {
  var ul = document.getElementById('dl_list');
  for( var i = 0; i < ul.childNodes.length; i++ ) {
    node = ul.childNodes[i];
    if( node.nodeName != 'LI' ) {
      continue;
    }
    node.style.display = 'block';
  }
}
