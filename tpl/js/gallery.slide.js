/**
 * gallery.slide.js for BoardDX
 * @author NHN (developers@xpressengine.com)
 * @optimizer phiDel (xe.phidel@gmail.com)
 **/
(function($){

	// 슬라이드를 위한 블랙 스크린을 만들거나 반환하는 함수
	var pidSlideScreen = {
		// 검은 스크린
		xScreen: $("<div>")
			.attr("id","pid_gallery_screen")
			.css({
				position:"fixed",
				display:"none",
				backgroundColor:"black",
				zIndex:500,
				opacity:0.6
			}).appendTo(document.body),
		// 이미지를 보여주고 컨트롤 버튼을 다룰 레이어
		controls: $("<div>")
			.attr("id","pid_gallery_controls")
			.css({
				position:"fixed",
				display:"none",
				overflow:"hidden",
				zIndex:510
			}).appendTo(document.body),
		// 닫기 버튼
		closebtn: $('<button type="button" id="pid_gallery_closebtn" />')
			.css({
				top: "10px",
				backgroundPosition: "0 0"
			}),
		// 이전 버튼
		prevbtn: $('<button type="button" id="pid_gallery_prevbtn" />')
			.css({
				left: "10px",
				backgroundPosition: "0 -64px"
			})
			.click(function(){pidSlideScreen.xePrev();}),
		// 다음 버튼
		nextbtn: $('<button type="button" id="pid_gallery_nextbtn" />')
			.attr("id", "pid_gallery_nextbtn")
			.css({
				right: "10px",
				backgroundPosition: "0 -128px"
			})
			.click(function(){pidSlideScreen.xeNext();}),
		// 이미지 홀더
		imgframe: $(new Image())
			.attr("id", "pid_gallery_holder")
			.css({
				border: '5px solid white',
				zindex: 520,
				maxWidth: 'none',
				borderRadius: '5px',
				boxShadow: '0 0 10px #000'
			}),
		// funcs
		xeShow: function() {
			var clientWidth  = $(window).width();
			var clientHeight = $(window).height();
			$("#pid_gallery_controls,#pid_gallery_screen").show().css({
				top		: 0,
				right   : 0,
				bottom	: 0,
				left	: 0
			});
			$("#pid_gallery_prevbtn,#pid_gallery_nextbtn").css("top", Math.round(clientHeight/2 - 32) + "px");
			this.xeMove(0);
		},
		xeHide: function(event) {
			this.xScreen.hide();
			this.controls.hide();
		},
		xePrev: function() {
			this.xeMove(-1);
		},
		xeNext: function() {
			this.xeMove(1);
		},
		xeMove: function(val) {
			var clientWidth  = $(window).width();
			var clientHeight = $(window).height();
			this.index += val;
			this.prevbtn.css("visibility", (this.index>0)?"visible":"hidden");
			this.nextbtn.css("visibility", (this.index<this.list.length-1)?"visible":"hidden");

			//textyle 이미지 리사이즈 처리
			var src = this.list[this.index];
			this.imgframe.attr("src", src).removeAttr('width').removeAttr('height');
			if(this.imgframe.width() > 0) {
				this.imgframe.css({
					left : clientWidth/2 - this.imgframe.width()/2 + "px",
					top  : clientHeight/2 - this.imgframe.height()/2 + "px"
				});
			}

			this.closebtn.css({
				left : clientWidth/2 - 32 + "px",
				top  : "10px"
			}).focus();
		}
	};

	pidSlideScreen.controls
		.append(pidSlideScreen.closebtn, pidSlideScreen.prevbtn, pidSlideScreen.nextbtn)
		.find(">button")
		.css({
			position : "absolute",
			width : "64px",
			height : "64px",
			zIndex : 530,
			cursor : "pointer",
			border : 0,
			margin : 0,
			padding : 0,
			backgroundColor: "transparent",
			backgroundImage: "url(" + request_uri + "addons/resize_image/btn.png)",
			backgroundRepeat: "no-repeat",
			opacity: ".5",
			filter: "alpha(opacity=50)"
		})
		.mouseover(function(){
			$(this).css({
				opacity: "1",
				filter: "alpha(opacity=100)"
			});
		})
		.mouseout(function(){
			$(this).css({
				opacity: ".5",
				filter: "alpha(opacity=50)"
			});
		})
		.focus(function(){
			$(this).trigger('mouseover');
		})
		.blur(function(){
			$(this).trigger('mouseout');
		});

	pidSlideScreen.imgframe
		.on('load', function(){
			var clientWidth  = $(window).width();
			var clientHeight = $(window).height();
			$(this).css({
				left : clientWidth/2 - $(this).width()/2 + "px",
				top  : clientHeight/2 - $(this).height()/2 + "px"
			});
		})
		.appendTo(pidSlideScreen.controls)
		.draggable();

	$.fn.pidSlideShow = function() {
		this
			.click(function() {
				var $this = $(this);
				if($this.data('state') === 'showing') $this.trigger('close.mw');
				$this.trigger('open.mw');
				return false;
			})
			.on('open.mw', function() {
				var $this = $(this), currentIdx;

				// before event trigger
				before_event = $.Event('before-open.mw');
				$this.trigger(before_event);
				// is event canceled?
				if (before_event.isDefaultPrevented()) return false;
				if(!this.manualShow && typeof this.imglist !== 'object') return false;

				$this.data('state', 'showing');

				pidSlideScreen.closebtn.click(function(){$this.trigger('close.mw');});
				if(this.manualShow){
					pidSlideScreen.list  = ['./../common/img/msg.loading.gif'];
				} else {
					pidSlideScreen.list  = this.imglist;
				}
				pidSlideScreen.index = 0;
				pidSlideScreen.xeShow();

				// 스크린을 닫는 상황
				$(document).keydown(function(e){
					if(e.which == 27){
						$this.trigger('close.mw');
						return false;
					} else {
						return true;
					}
				});

				// after event trigger
				var after = function() {
					$this.trigger('after-open.mw', [pidSlideScreen]);
				};

				after();
			})
			.on('close.mw', function() {
				var $this = $(this);

				// before event trigger
				before_event = $.Event('before-close.mw');
				$this.trigger(before_event);
				// is event canceled?
				if (before_event.isDefaultPrevented()) return false;

				pidSlideScreen.xeHide();

				$this.data('state', 'hiding');

				// after event trigger
				var after = function() {
					$this.trigger('after-close.mw');
				};

				after();
			});
	};

})(jQuery);
