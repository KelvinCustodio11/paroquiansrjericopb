(function ($) {
    "use strict";
	
	var $window = $(window); 
	var $body = $('body'); 

	/* Preloader Effect */
	$window.on('load', function(){
		$(".preloader").fadeOut(600);
	});
	
	/* Sticky Header */	
	if($('.active-sticky-header').length){
		$window.on('resize', function(){
			setHeaderHeight();
		});

		function setHeaderHeight(){
	 		$("header.main-header").css("height", $('header .header-sticky').outerHeight());
		}	
	
		$(window).on("scroll", function() {
			var fromTop = $(window).scrollTop();
			setHeaderHeight();
			var headerHeight = $('header .header-sticky').outerHeight()
			$("header .header-sticky").toggleClass("hide", (fromTop > headerHeight + 100));
			$("header .header-sticky").toggleClass("active", (fromTop > 600));
		});
	}	
	
	/* Slick Menu JS */
	$('#menu').slicknav({
		label : '',
		prependTo : '.responsive-menu'
	});

	if($("a[href='#top']").length){
		$("a[href='#top']").click(function() {
			$("html, body").animate({ scrollTop: 0 }, "slow");
			return false;
		});
	}

	/* Hero Slider Layout JS */
	const hero_slider_layout = new Swiper('.hero-slider-layout .swiper', {
		slidesPerView : 1,
		speed: 1000,
		spaceBetween: 0,
		loop: true,
		autoplay: {
			delay: 4000,
		},
		pagination: {
			el: '.hero-pagination',
			clickable: true,
		},
	});

	/* Core Value Image Carousel JS */
	if ($('.core-value-slider').length) {
		const core_value_slider = new Swiper('.core-value-slider .swiper', {
			slidesPerView : 1,
			speed: 1000,
			spaceBetween: 10,
			loop: true,
			autoplay: {
				delay: 5000,
			},
			navigation: {
				nextEl: '.core-value-button-next',
				prevEl: '.core-value-button-prev',
			},
		});
	}

	/* Service Single Image Carousel JS */
	if ($('.service-single-slider').length) {
		const service_single_slider = new Swiper('.service-single-slider .swiper', {
			slidesPerView : 1,
			speed: 1000,
			spaceBetween: 10,
			loop: true,
			autoplay: {
				delay: 5000,
			},
			navigation: {
				nextEl: '.service-single-button-next',
				prevEl: '.service-single-button-prev',
			},
		});
	}

	/* Ministry Single Image Carousel JS */
	if ($('.ministry-single-slider').length) {
		const ministry_single_slider = new Swiper('.ministry-single-slider .swiper', {
			slidesPerView : 1,
			speed: 1000,
			spaceBetween: 10,
			loop: true,
			autoplay: {
				delay: 5000,
			},
			pagination: {
				el: '.swiper-pagination',
				clickable: true,
			},
		});
	}

	/* Skill Bar */
	if ($('.skills-progress-bar').length) {
		$('.skills-progress-bar').waypoint(function() {
			$('.skillbar').each(function() {
				$(this).find('.count-bar').animate({
				width:$(this).attr('data-percent')
				},2000);
			});
		},{
			offset: '50%'
		});
	}

	/* Youtube Background Video JS */
	if ($('#herovideo').length) {
		var myPlayer = $("#herovideo").YTPlayer();
	}

	/* Audio JS */
	const player = new Plyr('#player');

	/* Init Counter */
	if ($('.counter').length) {
		$('.counter').counterUp({ delay: 6, time: 3000 });
	}

	/* Image Reveal Animation */
	if ($('.reveal').length) {
        gsap.registerPlugin(ScrollTrigger);
        let revealContainers = document.querySelectorAll(".reveal");
        revealContainers.forEach((container) => {
            let image = container.querySelector("img");
            let tl = gsap.timeline({
                scrollTrigger: {
                    trigger: container,
                    toggleActions: "play none none none"
                }
            });
            tl.set(container, {
                autoAlpha: 1
            });
            tl.from(container, 1, {
                xPercent: -100,
                ease: Power2.out
            });
            tl.from(image, 1, {
                xPercent: 100,
                scale: 1,
                delay: -1,
                ease: Power2.out
            });
        });
    }

	/* Text Effect Animation */
	if ($('.text-anime-style-1').length) {
		let staggerAmount 	= 0.05,
			translateXValue = 0,
			delayValue 		= 0.5,
		   animatedTextElements = document.querySelectorAll('.text-anime-style-1');
		
		animatedTextElements.forEach((element) => {
			let animationSplitText = new SplitText(element, { type: "chars, words" });
				gsap.from(animationSplitText.words, {
				duration: 1,
				delay: delayValue,
				x: 20,
				autoAlpha: 0,
				stagger: staggerAmount,
				scrollTrigger: { trigger: element, start: "top 85%" },
				});
		});		
	}
	
	if ($('.text-anime-style-2').length) {				
		let	 staggerAmount 		= 0.03,
			 translateXValue	= 20,
			 delayValue 		= 0.1,
			 easeType 			= "power2.out",
			 animatedTextElements = document.querySelectorAll('.text-anime-style-2');
		
		animatedTextElements.forEach((element) => {
			let animationSplitText = new SplitText(element, { type: "chars, words" });
				gsap.from(animationSplitText.chars, {
					duration: 1,
					delay: delayValue,
					x: translateXValue,
					autoAlpha: 0,
					stagger: staggerAmount,
					ease: easeType,
					scrollTrigger: { trigger: element, start: "top 85%"},
				});
		});		
	}
	
	if ($('.text-anime-style-3').length) {		
		let	animatedTextElements = document.querySelectorAll('.text-anime-style-3');
		
		 animatedTextElements.forEach((element) => {
			//Reset if needed
			if (element.animation) {
				element.animation.progress(1).kill();
				element.split.revert();
			}

			element.split = new SplitText(element, {
				type: "lines,words,chars",
				linesClass: "split-line",
			});
			gsap.set(element, { perspective: 400 });

			gsap.set(element.split.chars, {
				opacity: 0,
				x: "50",
			});

			element.animation = gsap.to(element.split.chars, {
				scrollTrigger: { trigger: element,	start: "top 90%" },
				x: "0",
				y: "0",
				rotateX: "0",
				opacity: 1,
				duration: 1,
				ease: Back.easeOut,
				stagger: 0.02,
			});
		});		
	}

	/* Parallaxie js */
	var $parallaxie = $('.parallaxie');
	if($parallaxie.length && ($window.width() > 991))
	{
		if ($window.width() > 768) {
			$parallaxie.parallaxie({
				speed: 0.55,
				offset: 0,
			});
		}
	}

	/* Zoom Gallery screenshot */
	$('.gallery-items').magnificPopup({
		delegate: 'a',
		type: 'image',
		closeOnContentClick: false,
		closeBtnInside: false,
		mainClass: 'mfp-with-zoom',
		image: {
			verticalFit: true,
		},
		gallery: {
			enabled: true
		},
		zoom: {
			enabled: true,
			duration: 300, // don't foget to change the duration also in CSS
			opener: function(element) {
			  return element.find('img');
			}
		}
	});

	/* Contact form validation */
	var $contactform = $("#contactForm");
	$contactform.validator({focus: false}).on("submit", function (event) {
		if (!event.isDefaultPrevented()) {
			event.preventDefault();
			submitForm();
		}
	});

	function submitForm(){
		/* Initiate Variables With Form Content*/
		var fname = $("#fname").val();
		var lname = $("#lname").val();
		var email = $("#email").val();
		var phone = $("#phone").val();
		var message = $("#msg").val();

		$.ajax({
			type: "POST",
			url: "form-process.php",
			data: "fname=" + fname + "&lname=" + lname + "&email=" + email + "&phone=" + phone + "&message=" + message,
			success : function(text){
				if (text == "success"){
					formSuccess();
				} else {
					submitMSG(false,text);
				}
			}
		});
	}

	function formSuccess(){
		$contactform[0].reset();
		submitMSG(true, "Message Sent Successfully!")
	}

	function submitMSG(valid, msg){
		if(valid){
			var msgClasses = "h3 text-success";
		} else {
			var msgClasses = "h3 text-danger";
		}
		$("#msgSubmit").removeClass().addClass(msgClasses).text(msg);
	}
	/* Contact form validation end */

	/* Animated Wow Js */	
	new WOW().init();

	/* Popup Video */
	if ($('.popup-video').length) {
		$('.popup-video').magnificPopup({
			type: 'iframe',
			mainClass: 'mfp-fade',
			removalDelay: 160,
			preloader: false,
			fixedContentPos: true
		});
	}

	/* Floating Action Buttons */
	(function () {
		var fabTop = document.getElementById('fabTopBtn');
		if (!fabTop) { return; }

		window.addEventListener('scroll', function () {
			if (window.scrollY > 300) {
				fabTop.classList.add('visible');
			} else {
				fabTop.classList.remove('visible');
			}
		}, { passive: true });

		fabTop.addEventListener('click', function () {
			window.scrollTo({ top: 0, behavior: 'smooth' });
		});
	}());

	/* Radio Player
	 * ─────────────────────────────────────────────────────────────────────────
	 * Substitua STREAM_URL pela URL de streaming da rádio (MP3/AAC/HLS).
	 * Exemplo: 'https://streaming.exemplo.com.br:8000/radio.mp3'
	 * ──────────────────────────────────────────────────────────────────────── */
	(function () {
		var STREAM_URL = 'URL_DO_STREAM_AQUI'; // ← Coloque aqui a URL do stream

		var audio      = document.getElementById('radioAudio');
		var playBtn    = document.getElementById('radioPlayBtn');
		var playIcon   = document.getElementById('radioPlayIcon');
		var volSlider  = document.getElementById('radioVolume');
		var volIcon    = document.getElementById('radioVolumeIcon');
		var closeBtn   = document.getElementById('radioCloseBtn');
		var playerBar  = document.getElementById('radioPlayerBar');

		if (!audio || !playerBar) { return; }

		var isPlaying  = false;

		function setPlaying(state) {
			isPlaying = state;
			playIcon.className = state ? 'fa-solid fa-pause' : 'fa-solid fa-play';
		}

		function updateVolIcon(vol) {
			if (vol <= 0) {
				volIcon.className = 'fa-solid fa-volume-xmark';
			} else if (vol < 0.5) {
				volIcon.className = 'fa-solid fa-volume-low';
			} else {
				volIcon.className = 'fa-solid fa-volume-high';
			}
		}

		playBtn.addEventListener('click', function () {
			if (isPlaying) {
				audio.pause();
				setPlaying(false);
			} else {
				if (!audio.src || audio.src === window.location.href) {
					audio.src = STREAM_URL;
				}
				audio.play().then(function () {
					setPlaying(true);
				}).catch(function () {
					setPlaying(false);
				});
			}
		});

		audio.addEventListener('ended', function () { setPlaying(false); });
		audio.addEventListener('error', function () { setPlaying(false); });

		volSlider.addEventListener('input', function () {
			audio.volume = parseFloat(this.value);
			updateVolIcon(audio.volume);
		});

		volIcon.addEventListener('click', function () {
			audio.volume = audio.volume > 0 ? 0 : 1;
			volSlider.value = audio.volume;
			updateVolIcon(audio.volume);
		});

		volIcon.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') { this.click(); }
		});

		closeBtn.addEventListener('click', function () {
			audio.pause();
			audio.src = '';
			setPlaying(false);
			playerBar.classList.remove('active');
		});

		document.querySelectorAll('[data-radio-trigger]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				playerBar.classList.add('active');
				if (!isPlaying) {
					audio.src = STREAM_URL;
					audio.play().then(function () {
						setPlaying(true);
					}).catch(function () {
						setPlaying(false);
					});
				}
			});
		});
	}());
			
})(jQuery);