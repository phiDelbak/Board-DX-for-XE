/**
 * @brief ajaxfileupload 2.1
 * @author PHPLETTER (http://www.phpletter.com/Our-Projects/AjaxFileUpload)
 * @Optimizer for XE by phiDel (xe.phidel@gmail.com)
 **/

jQuery(function($)
{
	$.fn.extend(
	{
		insertAtCaret: function(v)
		{
			var o = (typeof this[0].name !='undefined') ? this[0] : this;

			if ($.browser.msie)
			{
				o.focus();
				sel = document.selection.createRange();
				sel.text = v;
				o.focus();
			}
			else if ($.browser.mozilla || $.browser.webkit)
			{
				var s = o.selectionStart, d = o.selectionEnd, t = o.scrollTop;
				o.value = o.value.substring(0, s)+v+o.value.substring(d,o.value.length);
				o.focus();
				o.selectionStart = s + v.length;
				o.selectionEnd = s + v.length;
				o.scrollTop = t;
			}
			else
			{
				o.value += v;
				o.focus();
			}
		},
		evalScripts: function()
		{
			var o = this;
			$("script", o).each(function()
			{
				eval( o.text || o.textContent || o.innerHTML || "");
			});
		}
	});

	$.extend(
	{
		createUploadIframe: function(m, r)
		{
			var z = 'jUploadFrame' + m, $w = $(z), t;

			if(window.ActiveXObject && ((typeof r== 'boolean') || (typeof r== 'string')))
			{
				t = (typeof r== 'boolean') ? 'about:blink' : r;
			}

			if(!$w.parent(document.body).length)
			{
				//create frame
				$w = $('<iframe style="position:absolute; top:-9999px; left:-9999px" />')
					.attr({id:z, name:z}).attr('src', t).appendTo(document.body);
			}
			else $w.attr('src', t);

			return $w.get(0);
		},

		createUploadForm: function(m, u, a)
		{
			//create form
			var z = 'jUploadForm' + m, $f = $(z), $e = $('#' + u), $c = $e.clone();

			if(!$f.parent(document.body).length)
			{
				$f = $('<form  action="" method="POST" enctype="multipart/form-data" />')
					.attr({id:z, name:z}).css({position:'absolute',top:'-1200px',left:'-1200px'})
					.appendTo(document.body);
			}
			else $f.html('');

			if(typeof a == 'object')
			{
				for(var i in a)
				{
					$('<input type="hidden" name="' + i + '" value="' + a[i] + '" />').appendTo($f);
				}
			}

			$e.attr('id', 'jUploadFile' + m).before($c).appendTo($f);

			return $f;
		},

		ajaxFileUpload: function(s) {
			// TODO introduce global settings, allowing the client to modify them for all requests, not only timeout
			s = $.extend({}, $.ajaxSettings, s);

			var n = new Date().getTime(),
				o = $.createUploadForm(n, s.fileElementId, (typeof s.data=='undefined'?false:s.data)),
				w = $.createUploadIframe(n, s.secureuri),
				z = 'jUploadFrame' + n, x = 'jUploadForm' + n;

			// Watch for a new set of requests
			$.active++;
			if ( s.global && !$.active ) $.event.trigger( "ajaxStart" );

			var g = false,
				xml = {}; // Create the request object

			if ( s.global ) $.event.trigger("ajaxSend", [xml, s]);

			// Wait for a response to come back
			var uploadCallback = function(ist)
			{
				w = w ? w : document.getElementById(z);

				try
				{
					w = w.contentWindow ? w.contentWindow : w.contentDocument;
					xml.responseText = w.document.body ? w.document.body.innerHTML : null;
					xml.responseXML = w.document.XMLDocument ? w.document.XMLDocument : w.document;
				}
				catch(e)
				{
					//$.handleError(s, xml, null, e);
					alert(e);
				}

				if ( xml || ist == "timeout")
				{
					g = true;
					var sta;

					try {
						sta = ist != "timeout" ? "success" : "error";

						// Make sure that the request was successful or notmodified
						if ( sta != "error" )
						{
							// process the data (runs the xml through httpData regardless of callback)
							var data = $.uploadHttpData( xml, s.dataType );
							// If a local callback was specified, fire it and pass it the data
							if ( s.success ) s.success( z, data, sta );
							// Fire the global callback
							if( s.global ) $.event.trigger( "ajaxSuccess", [xml, s] );
						}
						else
						{
							if ( s.error )
							{
								s.error( e );
							}
							else alert(e);
						}
					}
					catch(e1)
					{
						//sta = "error";
						//$.handleError(s, xml, sta, e);
						alert(e1);
					}

					// The request was completed
					if( s.global ) $.event.trigger( "ajaxComplete", [xml, s] );
					// Handle the global AJAX counter
					if ( s.global && ! --$.active ) $.event.trigger( "ajaxStop" );
					// Process result
					if ( s.complete ) s.complete(xml, sta);

					$(w).unbind();

					setTimeout(function()
					{
						try
						{
							$(w).remove();
							$(o).remove();

						} catch(e)
						{
							//$.handleError(s, xml, null, e);
							alert(e);
						}

					}, 100);

					xml = null;
				}
			};

			// Timeout checker
			if ( s.timeout > 0 )
			{
				setTimeout(function()
				{
					// Check to see if the request is still happening
					if( !g ) uploadCallback( "timeout" );
				}, s.timeout);
			}
			try
			{
				var $f = $('#' + x);
				$f.attr({action:s.url, method:'POST', target:z});
				$f.attr($f.encoding ? 'encoding' : 'enctype', 'multipart/form-data');
				$f.submit();
			}
			catch(e)
			{
				//$.handleError(s, xml, null, e);
				alert(e);
			}

			$('#' + z).load(uploadCallback);
			return {abort: function () {}};

		},

		uploadHttpData: function( r, t ) {
			var v = (!t || t == "xml") ? r.responseXML : r.responseText;

			// If the type is "script", eval it in global context
			if ( t == "script" ) $.globalEval(v);
			// Get the JavaScript object, if JSON is used.
			if ( t == "json" ) eval("data = " + v);
			// evaluate scripts within html
			if ( t == "html" ) $("<div>").html(v).evalScripts();

			return v;
		}
	});

	pidAjaxFileUpload = function() {
		if(document.getElementById('Filedata').value === "") return alert("no file") || false;

		jQuery('#fUloader .fpv').empty();
		jQuery('<img src="./common/img/msg.loading.gif" style="display:none;">').appendTo('#fUloader .fpv')
		.ajaxStart(function(){jQuery(this).show();}).ajaxComplete(function(){jQuery(this).hide();});

		jQuery.ajaxFileUpload
		(
			{
				url:'index.php?act=procFileIframeUpload',
				secureuri:false,
				fileElementId:'Filedata',
				dataType: 'html',
				data:{
				mid: current_mid,
				editor_sequence: '1',
				uploadTargetSrl: '',
				manual_insert: 'true'
				},
				success: function (id, data, status)
				{
					if(typeof data.error != 'undefined')
					{
						if(data.error !== '')
						{
							alert(data.error);
						}
						else alert(data.msg);
					} else {
						var io = document.getElementById(id);
						io = io.contentWindow ? io.contentWindow : io.contentDocument;
						pidReloadFileList(io.uploaded_fileinfo.file_srl);
					}
				},
				error: function (data, status, e)
				{
					alert(e);
				}
			}
		);

		return false;
	};

	pidDeleteFile = function() {
		var r = jQuery('#siFiles').val();

		if(r && confirm('Do you want to delete a file?'))
		{
			exec_json(
				"file.procFileDelete",
				{ file_srls:r, editor_sequence:'1' },
				function() { pidReloadFileList(); }
			);
		}

		return false;
	};

	pidInsertFile = function() {
		var r = jQuery('#siFiles').val();

		if(r){
			var t, m = jQuery('#sif'+r).text(),
				src = jQuery("#sif"+r).attr('data-src'),
				u = src || '';

			if(!u.match(/\.(?:(jpe?g|png|gif))$/i)) {
				t = '<a href="'+u+'">'+m+'</a>';
			} else {
				t = '<img src="'+u+'" />';
			}

			jQuery('#siFf textarea[name=content]').insertAtCaret(t);
			jQuery('#siFf input[name=use_html]').attr('checked','checked');
		}

		return false;
	};

	pidFilePreview = function() {
		jQuery('#fUloader .fpv').empty();

		var t, r = jQuery('#siFiles').val(),
			src = jQuery("#sif"+r).attr('data-src'),
			u = src || '';

		if(!u.match(/\.(?:(jpe?g|png|gif))$/i)) {
			t = '<img src="./modules/editor/tpl/images/files.gif" />';
		} else {
			t = '<img src="'+u+'" />';
		}

		jQuery(t).appendTo('#fUloader .fpv');
	};

	pidClickUpload = function() {
		jQuery('.fd input[type=file]:first').trigger('click');
		return false;
	};

	pidReloadFileList = function(trl) {
		var ps = {
			mid : current_mid,
			editor_sequence   : '1',
			upload_target_srl : ''
		};

		function on_complete(ret) {
			var o, i, c = 0, r, z, x, m, v, fs, ls;

			o = document.getElementById('siFf');
			o.document_srl.value = ret.upload_target_srl;
			fs = ret.files;

			(ls = jQuery('#siFiles')).empty();
			jQuery('#fUloader .fpv').empty();

			if(fs && fs.length) {
				for(i=0,c=fs.length; i < c; i++) {
					v = fs[i];
					m = v.source_filename;
					x = v.download_url;
					r = v.file_srl;
					z = parseInt(v.file_size);
					z = z > 1024 ? Math.round(z / 1024) + 'kb' : z + 'byte';

					jQuery('<option id="sif' + r + '" value="' + r + '" data-src="' + x + '"></option>')
						.appendTo(ls).text(m + ' (' + z + ')').addClass(x !== "" ? 'success' : 'error');
				}

				if(trl !== undefined && trl) ls.val(trl);
				pidFilePreview();
			}

			jQuery('#siFileCnt').text(c);
		}

		exec_json("file.getFileList", ps, on_complete);
	};

    $('#fUloader:eq(0)').each(function(){    	
    	pidReloadFileList();

    	$('.scFup', this).click(function(){
    		return pidClickUpload();
    	});
    	$('.scFcl', this).click(function(){
    		return pidDeleteFile();
    	});
    	$('.scFin', this).click(function(){
    		return pidInsertFile();
    	});
    });
});
