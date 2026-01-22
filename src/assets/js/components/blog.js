/**
 * Blog Functionality
 *
 * Handles Ajax pagination, category filtering, and smooth scrolling
 *
 * @package SkylineWP Dev Child
 */

import $ from 'jquery';

(function () {
	'use strict';

	/**
	 * Blog Module
	 */
	const Blog = {
		// State
		isLoading: false,
		currentPage: 1,
		currentCategory: 0,

		// Selectors
		postsContainer: '#ats-blog-posts-container',
		paginationContainer: '.rfs-ref-blog-pagination',
		categoryLinks: '.rfs-ref-category-link',
		blogArchive: '.rfs-ref-blog-archive',

		/**
		 * Initialize the module
		 */
		init: function () {
			// Only run on blog archive pages
			if (!$(this.blogArchive).length) {
				return;
			}

			this.bindEvents();
			this.setupSmoothScroll();
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function () {
			const self = this;

			// Pagination clicks
			$(document).on('click', '.rfs-ref-blog-pagination a.page-numbers', function (e) {
				e.preventDefault();

				const $link = $(this);
				const href = $link.attr('href');

				if (!href || $link.hasClass('current') || self.isLoading) {
					return;
				}

				// Extract page number from URL
				const pageMatch = href.match(/paged=(\d+)|\/page\/(\d+)/);
				const page = pageMatch ? parseInt(pageMatch[1] || pageMatch[2]) : 1;

				self.loadPosts(page, self.currentCategory);
			});

			// Category filter clicks
			$(document).on('click', this.categoryLinks, function (e) {
				e.preventDefault();

				const $link = $(this);
				const href = $link.attr('href');

				if ($link.hasClass('active') || self.isLoading) {
					return;
				}

				// Extract category ID from URL
				const catMatch = href.match(/\/category\/([^\/]+)/);
				if (catMatch) {
					// Would need to convert slug to ID, for now just reload
					window.location.href = href;
					return;
				}

				// If category ID is in data attribute
				const categoryId = $link.data('category-id') || 0;

				// Update active state
				$(self.categoryLinks).removeClass('bg-primary-500 text-primary-900');
				$link.addClass('bg-primary-500 text-primary-900');

				self.currentCategory = categoryId;
				self.loadPosts(1, categoryId);
			});
		},

		/**
		 * Load posts via Ajax
		 * @param {number} page - Page number
		 * @param {number} category - Category ID
		 */
		loadPosts: function (page, category) {
			const self = this;

			if (this.isLoading) {
				return;
			}

			this.isLoading = true;
			this.currentPage = page;

			// Show loading state
			const $container = $(this.postsContainer);
			$container.css('opacity', '0.5');

			// Add loading overlay
			this.showLoadingOverlay();

			// Scroll to top of posts container
			this.scrollToTop();

			$.ajax({
				url: themeData.ajax_url,
				type: 'POST',
				data: {
					action: 'ats_load_blog_posts',
					nonce: themeData.blog_nonce || themeData.nonce,
					page: page,
					category: category,
					posts_per_page: 10,
				},
				success: function (response) {
					if (response.success) {
						// Update posts
						$container.html(response.posts);

						// Update pagination
						if (response.pagination) {
							$(self.paginationContainer).html(response.pagination);
						} else {
							$(self.paginationContainer).empty();
						}

						// Update URL without page reload
						self.updateUrl(page, category);

						// Trigger custom event
						$(document).trigger('ats_blog_posts_loaded', [response]);

						// Animate posts in
						self.animatePostsIn();
					} else {
						self.showMessage('Failed to load posts. Please try again.', 'error');
					}
				},
				error: function () {
					self.showMessage('An error occurred. Please refresh the page.', 'error');
				},
				complete: function () {
					self.isLoading = false;
					$container.css('opacity', '1');
					self.hideLoadingOverlay();
				},
			});
		},

		/**
		 * Show loading overlay
		 */
		showLoadingOverlay: function () {
			const $overlay = $(`
				<div class="ats-blog-loading-overlay fixed inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm">
					<div class="text-center">
						<div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-primary-500 border-t-transparent mb-4"></div>
						<p class="text-gray-600 font-medium">Loading posts...</p>
					</div>
				</div>
			`);

			$('body').append($overlay);

			// Prevent scrolling
			$('body').css('overflow', 'hidden');
		},

		/**
		 * Hide loading overlay
		 */
		hideLoadingOverlay: function () {
			$('.ats-blog-loading-overlay').fadeOut(300, function () {
				$(this).remove();
			});

			// Restore scrolling
			$('body').css('overflow', '');
		},

		/**
		 * Scroll to top of posts container
		 */
		scrollToTop: function () {
			const $archive = $(this.blogArchive);
			if ($archive.length) {
				$('html, body').animate(
					{
						scrollTop: $archive.offset().top - 100,
					},
					500,
					'swing'
				);
			}
		},

		/**
		 * Animate posts in with stagger effect
		 */
		animatePostsIn: function () {
			const $posts = $(this.postsContainer).find('.rfs-ref-blog-card');

			$posts.each(function (index) {
				const $post = $(this);
				$post.css({
					opacity: 0,
					transform: 'translateY(20px)',
				});

				setTimeout(() => {
					$post.css({
						transition: 'all 0.5s ease',
						opacity: 1,
						transform: 'translateY(0)',
					});
				}, index * 100);
			});
		},

		/**
		 * Update browser URL without page reload
		 * @param {number} page - Page number
		 * @param {number} category - Category ID
		 */
		updateUrl: function (page, category) {
			if (!window.history || !window.history.pushState) {
				return;
			}

			let url = window.location.pathname;

			// Add page parameter
			if (page > 1) {
				url = url.replace(/\/page\/\d+\/?/, '');
				url += url.endsWith('/') ? `page/${page}/` : `/page/${page}/`;
			}

			window.history.pushState({ page: page, category: category }, '', url);
		},

		/**
		 * Setup smooth scroll for anchor links
		 */
		setupSmoothScroll: function () {
			$(document).on('click', 'a[href^="#"]', function (e) {
				const target = $(this).attr('href');

				if (target && target !== '#' && $(target).length) {
					e.preventDefault();

					$('html, body').animate(
						{
							scrollTop: $(target).offset().top - 100,
						},
						600,
						'swing'
					);
				}
			});
		},

		/**
		 * Show success/error message
		 * @param {string} message - Message text
		 * @param {string} type - Message type (success/error)
		 */
		showMessage: function (message, type = 'success') {
			const $message = $(`
				<div class="ats-blog-message fixed top-20 right-4 z-50 max-w-sm p-4 rounded-lg shadow-lg ${
					type === 'success'
						? 'bg-green-50 border border-green-200 text-green-800'
						: 'bg-red-50 border border-red-200 text-red-800'
				} animate-slide-in-right">
					<div class="flex items-start gap-3">
						<svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
							${
								type === 'success'
									? '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'
									: '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>'
							}
						</svg>
						<p class="flex-1 text-sm font-medium">${message}</p>
					</div>
				</div>
			`);

			$('body').append($message);

			setTimeout(() => {
				$message.fadeOut(300, function () {
					$(this).remove();
				});
			}, 3000);
		},
	};

	/**
	 * Initialize on DOM ready
	 */
	$(document).ready(function () {
		Blog.init();
	});

	// Expose to window for external access if needed
	window.Blog = Blog;
})();
