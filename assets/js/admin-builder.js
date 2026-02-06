/**
 * Etch WP Menus - Admin Builder JavaScript
 */

(function($) {
  'use strict';

  const NavigationBuilder = {
    
    /**
     * Initialize the builder
     */
    init: function() {
      this.form = $('#nav-builder-form');
      this.breakpointInput = $('#mobile-breakpoint');
      this.generateButton = $('#generate-code');
      this.outputSection = $('#output-section');
      this.menuSelect = $('#menu-select');
      this.viewJsonButton = $('#view-menu-json');
      this.jsonModal = $('#menu-json-modal');
      
      this.bindEvents();
      this.initAnimationPreview();
    },
    
    /**
     * Bind event listeners
     */
    bindEvents: function() {
      // Breakpoint validation
      this.breakpointInput.on('input', this.validateBreakpoint.bind(this));
      
      // Generate code button
      this.generateButton.on('click', this.generateCode.bind(this));
      
      // Tab switching
      $('.etch-tab').on('click', this.switchTab.bind(this));
      
      // Copy code buttons
      $(document).on('click', '.etch-code-block__copy', this.copyCode.bind(this));
      
      // Animation preview
      $('input[name="hamburger_animation"]').on('change', this.updateAnimationPreview.bind(this));
      
      // Menu selector
      this.menuSelect.on('change', this.handleMenuChange.bind(this));
      this.viewJsonButton.on('click', this.showMenuJson.bind(this));
      
      // Modal
      $('.etch-modal__close, .etch-modal__overlay').on('click', this.closeModal.bind(this));
      
      // Approach toggle
      $('input[name="approach"]').on('change', this.handleApproachChange.bind(this));
      
      // Component property name preview
      $('#component-prop-name').on('input', this.updatePropNamePreview.bind(this));
    },
    
    /**
     * Validate breakpoint input
     */
    validateBreakpoint: function() {
      let value = parseInt(this.breakpointInput.val());
      
      if (value < 320) {
        value = 320;
      }
      if (value > 1920) {
        value = 1920;
      }
      
      this.breakpointInput.val(value);
    },
    
    /**
     * Get form data
     */
    getFormData: function() {
      const closeMethods = [];
      $('input[name="close_methods[]"]:checked').each(function() {
        closeMethods.push($(this).val());
      });
      
      const accessibility = [];
      $('input[name="accessibility[]"]:checked').each(function() {
        accessibility.push($(this).val());
      });
      
      return {
        menu_id: this.menuSelect.val(),
        approach: $('input[name="approach"]:checked').val(),
        component_prop_name: $('#component-prop-name').val(),
        mobile_breakpoint: parseInt(this.breakpointInput.val()),
        hamburger_animation: $('input[name="hamburger_animation"]:checked').val(),
        menu_position: $('input[name="menu_position"]:checked').val(),
        submenu_behavior: $('input[name="submenu_behavior"]:checked').val(),
        close_methods: closeMethods,
        accessibility: accessibility
      };
    },
    
    /**
     * Handle approach change (Direct Loop vs Component)
     */
    handleApproachChange: function() {
      const approach = $('input[name="approach"]:checked').val();
      
      if (approach === 'component') {
        $('.etch-component-settings').slideDown(200);
      } else {
        $('.etch-component-settings').slideUp(200);
      }
    },
    
    /**
     * Update property name preview
     */
    updatePropNamePreview: function() {
      const propName = $('#component-prop-name').val() || 'menuItems';
      $('#prop-name-preview').text(propName);
    },
    
    /**
     * Handle menu selection change
     */
    handleMenuChange: function() {
      const menuId = this.menuSelect.val();
      
      if (menuId) {
        this.viewJsonButton.prop('disabled', false);
      } else {
        this.viewJsonButton.prop('disabled', true);
      }
    },
    
    /**
     * Show menu JSON in modal
     */
    showMenuJson: function() {
      const menuId = this.menuSelect.val();
      
      if (!menuId) {
        alert('Please select a menu first.');
        return;
      }
      
      // Show loading
      $('#menu-json-output').text('Loading...');
      this.jsonModal.fadeIn(200);
      
      // Fetch menu JSON
      $.ajax({
        url: navBuilderData.ajaxurl,
        type: 'POST',
        data: {
          action: 'get_menu_json',
          nonce: navBuilderData.nonce,
          menu_id: menuId
        },
        success: function(response) {
          if (response.success) {
            $('#menu-json-output').text(response.data.json);
          } else {
            $('#menu-json-output').text('Error: ' + (response.data.message || 'Unknown error'));
          }
        },
        error: function() {
          $('#menu-json-output').text('Error communicating with server.');
        }
      });
    },
    
    /**
     * Close modal
     */
    closeModal: function(e) {
      if ($(e.target).hasClass('etch-modal__close') || 
          $(e.target).hasClass('etch-modal__overlay')) {
        this.jsonModal.fadeOut(200);
      }
    },
    
    /**
     * Generate navigation code
     */
    generateCode: function(e) {
      e.preventDefault();
      
      // Validate menu selection
      if (!this.menuSelect.val()) {
        alert('Please select a WordPress menu first.');
        this.menuSelect.focus();
        return;
      }
      
      const settings = this.getFormData();
      const button = this.generateButton;
      const originalText = button.text();
      
      // Show loading state
      button.prop('disabled', true).html('<span class="etch-loading"></span> Generating...');
      
      // Make AJAX request
      $.ajax({
        url: navBuilderData.ajaxurl,
        type: 'POST',
        data: {
          action: 'generate_navigation_code',
          nonce: navBuilderData.nonce,
          settings: JSON.stringify(settings)
        },
        success: function(response) {
          if (response.success) {
            NavigationBuilder.displayGeneratedCode(response.data);
            NavigationBuilder.scrollToOutput();
          } else {
            alert('Error generating code: ' + (response.data.message || 'Unknown error'));
          }
        },
        error: function() {
          alert('Error communicating with server. Please try again.');
        },
        complete: function() {
          button.prop('disabled', false).text(originalText);
        }
      });
    },
    
    /**
     * Display generated code
     */
    displayGeneratedCode: function(data) {
      // Populate code blocks
      $('#html-output').text(data.html);
      $('#css-output').text(data.css);
      $('#js-output').text(data.javascript);
      $('#etch-json-output').text(data.etch_json);
      
      // Convert markdown to HTML for quickstart
      const quickstartHtml = this.markdownToHtml(data.quickstart);
      $('#quickstart-output').html(quickstartHtml);
      
      // Show output section
      this.outputSection.fadeIn();
    },
    
    /**
     * Simple markdown to HTML converter
     */
    markdownToHtml: function(markdown) {
      let html = markdown;
      
      // Headers
      html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
      html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
      
      // Bold
      html = html.replace(/\*\*(.*?)\*\*/gim, '<strong>$1</strong>');
      
      // Code
      html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
      
      // Lists
      html = html.replace(/^\- (.*$)/gim, '<li>$1</li>');
      html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
      
      // Line breaks
      html = html.replace(/\n\n/g, '</p><p>');
      html = '<p>' + html + '</p>';
      
      // Clean up empty paragraphs
      html = html.replace(/<p>\s*<\/p>/g, '');
      html = html.replace(/<p>(\s*<h[23]>)/g, '$1');
      html = html.replace(/(<\/h[23]>)\s*<\/p>/g, '$1');
      html = html.replace(/<p>(\s*<ul>)/g, '$1');
      html = html.replace(/(<\/ul>)\s*<\/p>/g, '$1');
      
      return html;
    },
    
    /**
     * Scroll to output section
     */
    scrollToOutput: function() {
      $('html, body').animate({
        scrollTop: this.outputSection.offset().top - 100
      }, 500);
    },
    
    /**
     * Switch tabs
     */
    switchTab: function(e) {
      e.preventDefault();
      
      const $tab = $(e.currentTarget);
      const targetTab = $tab.data('tab');
      
      // Update active tab
      $('.etch-tab').removeClass('is-active');
      $tab.addClass('is-active');
      
      // Update active content
      $('.etch-tab-content').removeClass('is-active');
      $('.etch-tab-content[data-content="' + targetTab + '"]').addClass('is-active');
    },
    
    /**
     * Copy code to clipboard
     */
    copyCode: function(e) {
      const $button = $(e.currentTarget);
      const codeType = $button.data('copy');
      const $codeElement = $('#' + codeType + '-output');
      const code = $codeElement.text();
      
      // Create temporary textarea
      const $temp = $('<textarea>');
      $('body').append($temp);
      $temp.val(code).select();
      
      try {
        // Copy to clipboard
        document.execCommand('copy');
        
        // Show feedback
        const originalText = $button.text();
        $button.text('✓ Copied!').addClass('copied');
        
        setTimeout(function() {
          $button.text(originalText).removeClass('copied');
        }, 2000);
      } catch (err) {
        alert('Failed to copy code. Please copy manually.');
      }
      
      $temp.remove();
    },
    
    /**
     * Initialize animation preview
     */
    initAnimationPreview: function() {
      const $preview = $('.etch-preview-hamburger');
      
      $preview.on('click', function() {
        $(this).toggleClass('is-active');
      });
      
      this.updateAnimationPreview();
    },
    
    /**
     * Update animation preview based on selected type
     */
    updateAnimationPreview: function() {
      const animationType = $('input[name="hamburger_animation"]:checked').val();
      const $preview = $('.etch-preview-hamburger');
      const $lines = $preview.find('.etch-preview-line');
      
      // Remove existing animation classes
      $preview.removeClass('anim-spin anim-squeeze anim-collapse anim-arrow');
      
      // Add CSS for animation preview
      this.addAnimationPreviewStyles(animationType);
    },
    
    /**
     * Add dynamic styles for animation preview
     */
    addAnimationPreviewStyles: function(type) {
      // Remove existing preview styles
      $('#etch-preview-styles').remove();
      
      const animations = {
        spin: `
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(1) {
            transform: translateY(8px) rotate(45deg);
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(2) {
            transform: scaleX(0);
            opacity: 0;
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(3) {
            transform: translateY(-8px) rotate(-45deg);
          }
        `,
        squeeze: `
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(1) {
            transform: translateY(8px) rotate(45deg);
            width: 50%;
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(2) {
            transform: scaleX(0);
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(3) {
            transform: translateY(-8px) rotate(-45deg);
            width: 50%;
          }
        `,
        collapse: `
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(1),
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(2),
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(3) {
            transform: translateY(0);
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(2) {
            opacity: 0;
          }
        `,
        arrow: `
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(1) {
            transform: translateY(4px) rotate(-45deg);
            width: 70%;
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(2) {
            transform: translateX(10px);
          }
          .etch-preview-hamburger.is-active .etch-preview-line:nth-child(3) {
            transform: translateY(-4px) rotate(45deg);
            width: 70%;
          }
        `
      };
      
      const css = animations[type] || animations.spin;
      $('<style id="etch-preview-styles">' + css + '</style>').appendTo('head');
    }
  };

  // Initialize when document is ready
  $(document).ready(function() {
    NavigationBuilder.init();
  });

})(jQuery);
