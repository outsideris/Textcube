// Google Map Plugin UI Helper
// - depends on MooTools and Google AJAX API with Maps

var map;
var listener_onclick = null;
var user_markers = {};

function initialize() {
	initializeMap();
	$('toggleMarkerAddingMode').store('toggled', false);
	$('toggleMarkerAddingMode').addEvent('click', function() {
		this.store('toggled', !this.retrieve('toggled'));
		if (this.retrieve('toggled')) {
			this.setProperty('class', 'toggled');
			listener_onclick = GEvent.addListener(map, 'click', GMap_onClick);
		} else {
			this.setProperty('class', '');
			GEvent.removeListener(listener_onclick);
		}
	});
	$('applyBasicSettings').addEvent('click', function() {
		var gmp = $(map.getContainer());
		var w = $('inputWidth').value, h = $('inputHeight').value;
		if (w < 150 || h < 150) {
			alert('지도 크기가 너무 작습니다.');
			return;
		}
		gmp.set('styles', {
			'width': w + 'px',
			'height': h + 'px'
		});
		map.checkResize();
	});
	$('doInsert').addEvent('click', function() {
		if (!map)
			return;
		try {
			var editor = window.opener.editor;
			var options = {};
			var center = map.getCenter();
			options.center = {};
			options.center.latitude = center.lat();
			options.center.longitude = center.lng();
			options.zoom = map.getZoom();
			options.width = $('GoogleMapPreview').getSize().x;
			options.height = $('GoogleMapPreview').getSize().y;
			var compact_user_markers = new Array();
			var i = 0, id = '';
			for (id in user_markers) {
				compact_user_markers[i] = {
					'title': user_markers[id].title,
					'desc': user_markers[id].desc,
					'lat': user_markers[id].marker.getLatLng().lat(),
					'lng': user_markers[id].marker.getLatLng().lng()
				};
				i++;
			}
			options.user_markers = compact_user_markers;
			editor.command('Raw', '[##_GoogleMap|' + JSON.encode(options) + '|_##]');
			self.close();
		} catch (e) {
			alert('Parent window is not accessible. Is it closed?');
		}
	});
}

function findUserMarker(marker) {
	var id;
	for (id in user_markers) {
		if (user_markers[id].marker == marker)
			return user_markers[id];
	}
	return null;
}

function findUserMarkerById(id) {
	var i;
	try {
		return user_markers[id];
	} catch (e) {
		return null;
	}
}

function removeUserMarker(id) {
	map.removeOverlay(user_markers[id].marker);
	delete user_markers[id];
}

function GMap_onClick(overlay, latlng, overlaylatlng) {
	if (overlay == null) { // when empty area is clicked
		if (user_markers.length == 20) {
			alert('Too many markers!');
			return;
		}
		var marker = new GMarker(latlng, {'clickable': true, 'draggable': true, 'bouncy': true, 'title': 'Click to edit'});
		var id = 'um' + (new Date).valueOf() + (Math.ceil(Math.random()*90)+10);
		GEvent.addListener(marker, 'click', GMarker_onClick);
		GEvent.addListener(marker, 'infowindowbeforeclose', function() {
			user_markers[id].title = $('info_title').value;
			user_markers[id].desc = $('info_desc').value;
		});
		user_markers[id] = {'marker': marker, 'title': '', 'desc': '', 'id': id};
		map.addOverlay(marker);
	}
}

function GMarker_onClick(latlng) {
	var um = findUserMarker(this);
	var form = '<div class="GMapInfo">';
	form += '<p><label for="info_title">제목 : </label><input id="info_title" type="text" value="'+um.title+'" /></p>';
	form += '<p><label for="info_desc">설명 : </label><textarea id="info_desc" rows="5" cols="30">'+um.desc+'</textarea></p>';
	form += '<p style="text-align:right"><a href="javascript:void(0);" onclick="removeUserMarker(\''+um.id+'\');">삭제하기</p></div>';
	this.openInfoWindowHtml(form);
}