/**
 * Bộ cấu hình gia công thanh nhôm: chiều dài, lỗ khoan, taro — vẽ sơ đồ và tính giá trực tiếp.
 * Công thức calc() phải khớp với NDH_Gia_Cong::calc() trong PHP (PHP là nơi tính giá thật).
 */
( function () {
	var root = document.querySelector( '.ndh-config' );
	if ( ! root ) {
		return;
	}
	var cfg = JSON.parse( root.getAttribute( 'data-config' ) );
	var form = root.closest( 'form' );
	var lenInput = root.querySelector( '#ndh_len' );
	var list = root.querySelector( '.ndh-holes__list' );
	var tpl = root.querySelector( '.ndh-hole-tpl' );
	var svg = root.querySelector( '.ndh-bar__svg' );
	var totalEl = root.querySelector( '.ndh-total' );
	var rowsEl = root.querySelector( '.ndh-summary__rows' );
	var grandEl = root.querySelector( '.ndh-grand' );
	var qtyInput = form ? form.querySelector( 'input.qty' ) : null;
	var counter = list ? list.children.length : 0;
	var SVGNS = 'http://www.w3.org/2000/svg';

	var money = function ( n ) {
		return new Intl.NumberFormat( 'vi-VN' ).format( Math.round( n ) ) + ' ₫';
	};
	var num = function ( v ) {
		var n = parseFloat( String( v ).replace( ',', '.' ) );
		return isNaN( n ) ? 0 : n;
	};

	function readSpec() {
		var holes = [];
		if ( list ) {
			list.querySelectorAll( '.ndh-hole' ).forEach( function ( row ) {
				var pos = row.querySelector( '.ndh-hole__pos' );
				if ( pos.value !== '' ) {
					holes.push( { el: pos, pos: num( pos.value ), face: row.querySelector( '.ndh-hole__face' ).value } );
				}
			} );
		}
		var tap = function ( end ) {
			var s = root.querySelector( 'select[name="ndh_tap_' + end + '"]' );
			return s ? s.value : '';
		};
		return { len: num( lenInput.value ), holes: holes, tapA: tap( 'a' ), tapB: tap( 'b' ) };
	}

	function calc( spec ) {
		var parts = {
			material: cfg.price_m * spec.len / 1000,
			cut: cfg.cut_fee,
			drill: spec.holes.length * cfg.drill_fee,
			tap: ( ( spec.tapA ? 1 : 0 ) + ( spec.tapB ? 1 : 0 ) ) * cfg.tap_fee
		};
		var sum = parts.material + parts.cut + parts.drill + parts.tap;
		return { parts: parts, total: Math.ceil( sum / 100 ) * 100 };
	}

	function validate( spec ) {
		var lenOk = spec.len >= cfg.min_len && spec.len <= cfg.max_len;
		lenInput.setCustomValidity( lenOk || ! lenInput.value ? '' : 'Chiều dài phải từ ' + cfg.min_len + ' đến ' + cfg.max_len + ' mm.' );
		spec.holes.forEach( function ( h ) {
			var ok = h.pos >= cfg.edge_min && h.pos <= spec.len - cfg.edge_min;
			h.el.setCustomValidity( ok ? '' : 'Lỗ phải cách hai đầu thanh ít nhất ' + cfg.edge_min + ' mm (0 < vị trí < chiều dài).' );
			h.el.classList.toggle( 'is-invalid', ! ok );
		} );
		return lenOk;
	}

	function el( name, attrs, text ) {
		var n = document.createElementNS( SVGNS, name );
		Object.keys( attrs ).forEach( function ( k ) {
			n.setAttribute( k, attrs[ k ] );
		} );
		if ( text ) {
			n.textContent = text;
		}
		return n;
	}

	function draw( spec ) {
		while ( svg.firstChild ) {
			svg.removeChild( svg.firstChild );
		}
		var x0 = 40, x1 = 560, y0 = 28, y1 = 56;
		svg.appendChild( el( 'rect', { x: x0, y: y0, width: x1 - x0, height: y1 - y0, rx: 2, class: 'ndh-svg-bar' } ) );
		svg.appendChild( el( 'line', { x1: x0, y1: ( y0 + y1 ) / 2, x2: x1, y2: ( y0 + y1 ) / 2, class: 'ndh-svg-slot' } ) );
		svg.appendChild( el( 'text', { x: x0 - 8, y: 46, class: 'ndh-svg-end', 'text-anchor': 'end' }, 'A' ) );
		svg.appendChild( el( 'text', { x: x1 + 8, y: 46, class: 'ndh-svg-end' }, 'B' ) );

		[ [ spec.tapA, x0, 'start' ], [ spec.tapB, x1, 'end' ] ].forEach( function ( t ) {
			if ( ! t[ 0 ] ) {
				return;
			}
			var dir = t[ 2 ] === 'start' ? 1 : -1;
			for ( var i = 0; i < 4; i++ ) {
				svg.appendChild( el( 'line', { x1: t[ 1 ] + dir * ( 3 + i * 4 ), y1: y0 + 6, x2: t[ 1 ] + dir * ( 7 + i * 4 ), y2: y1 - 6, class: 'ndh-svg-thread' } ) );
			}
			svg.appendChild( el( 'text', { x: t[ 1 ], y: 20, class: 'ndh-svg-tap', 'text-anchor': t[ 2 ] }, t[ 0 ] ) );
		} );

		if ( spec.len > 0 ) {
			spec.holes.forEach( function ( h ) {
				var x = x0 + ( x1 - x0 ) * Math.min( Math.max( h.pos / spec.len, 0 ), 1 );
				var bad = h.el.classList.contains( 'is-invalid' );
				svg.appendChild( el( 'circle', { cx: x, cy: ( y0 + y1 ) / 2, r: 5, class: bad ? 'ndh-svg-hole is-bad' : 'ndh-svg-hole' } ) );
				svg.appendChild( el( 'text', { x: x, y: 70, class: 'ndh-svg-dim', 'text-anchor': 'middle' }, String( h.pos ) ) );
			} );
			svg.appendChild( el( 'text', { x: 300, y: 86, class: 'ndh-svg-len', 'text-anchor': 'middle' }, 'L = ' + spec.len + ' mm' ) );
		} else {
			svg.appendChild( el( 'text', { x: 300, y: 86, class: 'ndh-svg-len', 'text-anchor': 'middle' }, 'Nhập chiều dài để xem sơ đồ' ) );
		}
	}

	function update() {
		var spec = readSpec();
		var ok = validate( spec );
		draw( spec );
		if ( ! spec.len || ! ok ) {
			totalEl.textContent = '—';
			grandEl.textContent = '';
			rowsEl.innerHTML = '';
			return;
		}
		var r = calc( spec );
		var rows = [
			[ 'Nhôm ' + spec.len + ' mm × ' + money( cfg.price_m ) + '/m', r.parts.material ],
			[ 'Phí cắt', r.parts.cut ]
		];
		if ( spec.holes.length ) {
			rows.push( [ 'Khoan ' + spec.holes.length + ' lỗ', r.parts.drill ] );
		}
		var taps = ( spec.tapA ? 1 : 0 ) + ( spec.tapB ? 1 : 0 );
		if ( taps ) {
			rows.push( [ 'Taro ' + taps + ' đầu', r.parts.tap ] );
		}
		var qty = qtyInput ? Math.max( 1, parseInt( qtyInput.value, 10 ) || 1 ) : 1;
		rowsEl.innerHTML = '';
		rows.forEach( function ( row ) {
			var d = document.createElement( 'div' );
			d.innerHTML = '<span></span><span></span>';
			d.children[ 0 ].textContent = row[ 0 ];
			d.children[ 1 ].textContent = money( row[ 1 ] );
			rowsEl.appendChild( d );
		} );
		totalEl.textContent = money( r.total );
		grandEl.textContent = qty > 1 ? qty + ' thanh: ' + money( r.total * qty ) : '';
	}

	function addHole( pos ) {
		var html = tpl.innerHTML.replace( /__i__/g, String( counter++ ) );
		var wrap = document.createElement( 'div' );
		wrap.innerHTML = html.trim();
		var row = wrap.firstChild;
		if ( typeof pos === 'number' ) {
			row.querySelector( '.ndh-hole__pos' ).value = pos;
		}
		list.appendChild( row );
		return row;
	}

	if ( list ) {
		root.querySelector( '.ndh-add-hole' ).addEventListener( 'click', function () {
			if ( list.children.length >= cfg.max_holes ) {
				return;
			}
			addHole().querySelector( '.ndh-hole__pos' ).focus();
			update();
		} );
		root.querySelector( '.ndh-add-even' ).addEventListener( 'click', function () {
			var len = num( lenInput.value );
			if ( ! len ) {
				lenInput.focus();
				return;
			}
			var n = parseInt( window.prompt( 'Số lỗ cần chia đều?', '3' ), 10 );
			if ( ! n || n < 1 ) {
				return;
			}
			var m = num( window.prompt( 'Lỗ đầu/cuối cách đầu thanh bao nhiêu mm?', '20' ) );
			n = Math.min( n, cfg.max_holes );
			list.innerHTML = '';
			for ( var i = 0; i < n; i++ ) {
				var p = n === 1 ? len / 2 : m + i * ( len - 2 * m ) / ( n - 1 );
				addHole( Math.round( p * 2 ) / 2 );
			}
			update();
		} );
		list.addEventListener( 'click', function ( e ) {
			if ( e.target.classList.contains( 'ndh-hole__remove' ) ) {
				e.target.closest( '.ndh-hole' ).remove();
				update();
			}
		} );
	}

	root.addEventListener( 'input', update );
	root.addEventListener( 'change', update );
	if ( qtyInput ) {
		qtyInput.addEventListener( 'input', update );
		qtyInput.addEventListener( 'change', update );
	}
	update();
} )();
