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
	 * Substitua ITACAMBARI_STREAM pela URL de streaming da Rádio Itacambarí FM.
	 * Exemplo: 'https://streaming.exemplo.com.br:8000/radio.mp3'
	 * ──────────────────────────────────────────────────────────────────────── */
	(function () {
		var ITACAMBARI_STREAM = 'URL_DO_STREAM_AQUI'; // ← URL da Rádio Itacambarí FM
		var ITACAMBARI_NAME   = 'Rádio Itacambarí FM — Jericó/PB';

		var audio       = document.getElementById('radioAudio');
		var playBtn     = document.getElementById('radioPlayBtn');
		var playIcon    = document.getElementById('radioPlayIcon');
		var volSlider   = document.getElementById('radioVolume');
		var volIcon     = document.getElementById('radioVolumeIcon');
		var closeBtn    = document.getElementById('radioCloseBtn');
		var playerBar   = document.getElementById('radioPlayerBar');
		var stationName = document.getElementById('radioStationName');
		var listBtn     = document.getElementById('radioListBtn');
		var panel       = document.getElementById('radioStationPanel');
		var panelClose  = document.getElementById('radioPanelClose');
		var stationList = document.getElementById('radioStationList');

		if (!audio || !playerBar) { return; }

		var isPlaying   = false;
		var currentUrl  = ITACAMBARI_STREAM;
		var stationsLoaded = false;

		/* ── Helpers ── */
		function setPlaying(state) {
			isPlaying = state;
			playIcon.className = state ? 'fa-solid fa-pause' : 'fa-solid fa-play';
		}

		function updateVolIcon(vol) {
			if (vol <= 0)       volIcon.className = 'fa-solid fa-volume-xmark';
			else if (vol < 0.5) volIcon.className = 'fa-solid fa-volume-low';
			else                volIcon.className = 'fa-solid fa-volume-high';
		}

		function playStream(url, name) {
			currentUrl = url;
			if (stationName) stationName.textContent = name;
			audio.src = url;
			audio.play().then(function () { setPlaying(true); })
			            .catch(function () { setPlaying(false); });
			/* mark active item */
			document.querySelectorAll('.radio-station-item').forEach(function (el) {
				el.classList.toggle('active', el.dataset.url === url);
			});
		}

		/* ── Verifica se é domingo das 8h às 9h30 (horário local) ── */
		function isItacambariLive() {
			var now  = new Date();
			var day  = now.getDay();   // 0 = domingo
			var hour = now.getHours();
			var min  = now.getMinutes();
			var totalMin = hour * 60 + min;
			return day === 0 && totalMin >= 480 && totalMin < 570; // 8h00–9h30
		}

		/* ── Monta item na lista ── */
		function buildItem(station, isFeatured, badgeText) {
			var btn = document.createElement('button');
			btn.className = 'radio-station-item' + (isFeatured ? ' featured' : '');
			btn.dataset.url = station.url_resolved || station.url;
			if (currentUrl === btn.dataset.url) btn.classList.add('active');

			var favicon = station.favicon
				? '<img class="radio-station-favicon" src="' + station.favicon + '" alt="" onerror="this.style.display=\'none\'">'
				: '<div class="radio-station-favicon"></div>';

			var badge = badgeText
				? '<span class="radio-station-badge ' + (isItacambariLive() ? 'badge-live' : 'badge-featured') + '">' + badgeText + '</span>'
				: '';

			btn.innerHTML =
				favicon +
				'<div class="radio-station-item-info">' +
					'<div class="radio-station-item-name">' + station.name + '</div>' +
					'<div class="radio-station-item-meta">' + (station.state || station.country || '') + '</div>' +
				'</div>' +
				badge;

			btn.addEventListener('click', function () {
				playStream(btn.dataset.url, station.name);
				togglePanel(false);
			});

			return btn;
		}

		/* ── Carrega lista via Radio Browser API ── */
		function loadStations() {
			if (stationsLoaded) { return; }
			stationsLoaded = true;

			stationList.innerHTML = '<div class="radio-station-loading"><i class="fa-solid fa-circle-notch fa-spin"></i> Buscando rádios...</div>';

			/* Resolve servidor via DNS (recomendado pela API) */
			fetch('https://de1.api.radio-browser.info/json/stations/search?tag=catholic&countrycode=BR&limit=30&order=votes&reverse=true&hidebroken=true')
				.then(function (res) { return res.json(); })
				.then(function (stations) {
					stationList.innerHTML = '';

					/* Itacambarí sempre no topo */
					var itacambari = {
						name: ITACAMBARI_NAME,
						url_resolved: ITACAMBARI_STREAM,
						favicon: '',
						state: 'Jericó/PB',
						country: 'BR'
					};
					var featuredBadge = isItacambariLive() ? 'AO VIVO' : 'Paróquial';
					stationList.appendChild(buildItem(itacambari, true, featuredBadge));

					if (!stations || stations.length === 0) {
						var empty = document.createElement('div');
						empty.className = 'radio-station-loading';
						empty.textContent = 'Nenhuma outra rádio encontrada.';
						stationList.appendChild(empty);
						return;
					}

					stations.forEach(function (s) {
						stationList.appendChild(buildItem(s, false, null));
					});
				})
				.catch(function () {
					stationList.innerHTML = '';
					/* Fallback: só Itacambarí */
					var itacambari = {
						name: ITACAMBARI_NAME,
						url_resolved: ITACAMBARI_STREAM,
						favicon: '',
						state: 'Jericó/PB'
					};
					stationList.appendChild(buildItem(itacambari, true, isItacambariLive() ? 'AO VIVO' : 'Paróquial'));
					var err = document.createElement('div');
					err.className = 'radio-station-loading';
					err.textContent = 'Não foi possível carregar outras rádios.';
					stationList.appendChild(err);
				});
		}

		/* ── Painel toggle ── */
		function togglePanel(forceOpen) {
			var open = forceOpen !== undefined ? forceOpen : !panel.classList.contains('open');
			panel.classList.toggle('open', open);
			listBtn.classList.toggle('active', open);
			if (open) { loadStations(); }
		}

		listBtn.addEventListener('click', function () { togglePanel(); });
		panelClose.addEventListener('click', function () { togglePanel(false); });

		/* ── Controles de reprodução ── */
		playBtn.addEventListener('click', function () {
			if (isPlaying) {
				audio.pause();
				setPlaying(false);
			} else {
				playStream(currentUrl, stationName ? stationName.textContent : ITACAMBARI_NAME);
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
			togglePanel(false);
			playerBar.classList.remove('active');
		});

		document.querySelectorAll('[data-radio-trigger]').forEach(function (el) {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				playerBar.classList.add('active');
				if (!isPlaying) {
					playStream(currentUrl, stationName ? stationName.textContent : ITACAMBARI_NAME);
				}
			});
		});
	}());
			
})(jQuery);