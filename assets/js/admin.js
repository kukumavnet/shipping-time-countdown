jQuery(document).ready(function($) {
  // Slider functionality
  const slideshow = $('.blog-slideshow');
  const slides = $('.slide');
  const dots = $('.dot');
  let currentSlide = 0;
  const totalSlides = slides.length;

  // Initialize first slide
  slides.hide();
  slides.first().show();
  dots.first().addClass('active');

  // Previous slide button
  $('.slide-prev').click(function() {
      updateSlide('prev');
  });

  // Next slide button
  $('.slide-next').click(function() {
      updateSlide('next');
  });

  // Dot navigation
  $('.dot').click(function() {
      currentSlide = $(this).data('slide');
      updateSlide();
  });

  function updateSlide(direction = 'next') {
      // Hide all slides and remove active class
      $('.slide').hide().removeClass('active');
      
      // Calculate new slide position
      if (direction === 'next') {
          currentSlide = (currentSlide + 1) % totalSlides;
      } else {
          currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
      }

      // Show new active slide
      const newSlide = $(`#slide-${currentSlide}`);
      newSlide.fadeIn(300).addClass('active').css({
          'position': 'relative',
          'display': 'block'
      });

      // Update dot navigation
      $('.dot').removeClass('active');
      $(`.dot[data-slide="${currentSlide}"]`).addClass('active');
  }

  // Optional: Auto slide
  
  // Timer variables for auto slide
  let slideTimer;
  let isPaused = false;

  // Start auto slide timer
  function startSlideTimer() {
      if (slideTimer) clearInterval(slideTimer);
      
      slideTimer = setInterval(function() {
          if (!isPaused) {
              updateSlide('next');
          }
      }, 5000); // Change slide every 5 seconds
  }

  // Pause and resume slider functions
  function pauseSlider() {
      isPaused = true;
  }

  function resumeSlider() {
      isPaused = false;
  }

  // Mouse events
  slideshow.on('mouseenter touchstart', pauseSlider);
  slideshow.on('mouseleave touchend', resumeSlider);

  // Start initial timer
  startSlideTimer();

  // Dragging variables
  let isDragging = false;
  let startPos = 0;
  let currentTranslate = 0;
  let prevTranslate = 0;
  let animationID = 0;
  let startTime = 0;
  let dragThreshold = 50; // Minimum drag distance

  const container = $('.slides-container');
  
  // Touch and mouse events
  container.on('touchstart mousedown', dragStart);
  container.on('touchend mouseup', dragEnd);
  container.on('touchmove mousemove', drag);
  container.on('contextmenu', e => e.preventDefault());

  function dragStart(e) {
      // Initialize drag start values
      startTime = new Date().getTime();
      isDragging = true;
      container.addClass('dragging');
      
      const position = e.type === 'mousedown' ? e.pageX : e.touches[0].clientX;
      startPos = position;
      
      cancelAnimationFrame(animationID);
      pauseSlider();
  }

  function drag(e) {
      if (!isDragging) return;
      
      e.preventDefault();
      const position = e.type === 'mousemove' ? e.pageX : e.touches[0].clientX;
      const walk = position - startPos;
      
      // Add moving class if minimum threshold is met
      if (Math.abs(walk) > 5) {
          container.addClass('moving');
      }
  }

  function dragEnd(e) {
      if (!isDragging) return;
      
      const position = e.type === 'mouseup' ? e.pageX : e.changedTouches[0].clientX;
      const walk = position - startPos;
      const dragDuration = new Date().getTime() - startTime;
      
      // Lower threshold for quick swipes
      const effectiveThreshold = dragDuration < 250 ? dragThreshold / 2 : dragThreshold;
      
      if (Math.abs(walk) > effectiveThreshold) {
          // Handle slide direction
          if (walk > 0) {
              updateSlide('prev');
          } else {
              updateSlide('next');
          }
      }
      
      isDragging = false;
      container.removeClass('dragging moving');
      resumeSlider();
  }

  // Calculate items per slide based on screen width
  function getItemsPerSlide() {
      const width = $(window).width();
      if (width < 600) return 1;
      if (width < 1024) return 2;
      return 3;
  }

  // Reorganize slides
  function reorganizeSlides() {
      const itemsPerSlide = getItemsPerSlide();
      const blogItems = $('.blog-item').detach();
      const slides = $('.slide').remove();
      
      let currentSlide = $('<div class="slide"><div class="blog-grid"></div></div>');
      let counter = 0;
      let slideIndex = 0;

      // Distribute blog items according to new layout
      blogItems.each(function(index) {
          if (counter === 0) {
              currentSlide = $('<div id="slide-' + slideIndex + '" class="slide' + (slideIndex === 0 ? ' active' : '') + '"><div class="blog-grid"></div></div>');
              $('.slides-container').append(currentSlide);
          }

          currentSlide.find('.blog-grid').append($(this));
          counter++;

          if (counter === itemsPerSlide) {
              counter = 0;
              slideIndex++;
          }
      });

      // Adjust last slide
      if (counter > 0) {
          currentSlide.addClass(slideIndex === 0 ? 'active' : '').css({
              'position': 'relative',
              'display': slideIndex === 0 ? 'block' : 'none'
          });
      }

      // Update dot navigation
      updateDots(Math.ceil(blogItems.length / itemsPerSlide));
  }

  // Update dot navigation
  function updateDots(totalSlides) {
      const dotsContainer = $('.slide-dots');
      dotsContainer.empty();
      
      for (let i = 0; i < totalSlides; i++) {
          const dot = $('<button class="dot" data-slide="' + i + '"></button>');
          if (i === currentSlide) {
              dot.addClass('active');
          }
          dotsContainer.append(dot);
      }

      // Rebind dot click events
      $('.dot').click(function() {
          $('.dot').removeClass('active');
          $(this).addClass('active');
          currentSlide = $(this).data('slide');
          updateSlide();
      });
  }

  // Reorganize on window resize
  let resizeTimer;
  $(window).on('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
          reorganizeSlides();
          currentSlide = 0;
          updateSlide();
          startSlideTimer(); // Restart timer
      }, 250);
  });

  // Initial load
  reorganizeSlides();

  // Make entire blog card clickable
  $('.blog-item').on('click', function(e) {
      // Prevent duplicate clicks on links
      if (!$(e.target).is('a')) {
          const link = $(this).find('h3 a').attr('href');
          if (link) {
              window.open(link, '_blank');
          }
      }
  });

  // Add clickable appearance to cards
  $('.blog-item').css('cursor', 'pointer');
  
});
