(function ($) {
  "use strict";

  class CommunityHubPro {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.initSearch();
      this.initVoting();
      this.initSortTabs();
      this.initLoadMore();
    }

    bindEvents() {
      $(document).ready(() => {
        this.onDocumentReady();
      });
    }

    onDocumentReady() {
      console.log("Community Hub Pro initialized");
      this.addAnimations();
    }

    // Search functionality
    initSearch() {
      const $searchInput = $("#community-search");
      let searchTimeout;

      $searchInput.on("input", (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) {
          this.resetSearch();
          return;
        }

        searchTimeout = setTimeout(() => {
          this.performSearch(query);
        }, 300);
      });

      $searchInput.on("keypress", (e) => {
        if (e.which === 13) {
          e.preventDefault();
          this.performSearch(e.target.value.trim());
        }
      });
    }

    performSearch(query) {
      console.log("Searching for:", query);

      // Show loading state
      this.showSearchLoading(true);

      // AJAX search
      $.ajax({
        url: communityHub.ajaxurl,
        type: "POST",
        data: {
          action: "ch_search_posts",
          query: query,
          nonce: communityHub.nonce,
        },
        success: (response) => {
          if (response.success) {
            this.displaySearchResults(response.data);
          } else {
            this.showMessage("Search failed. Please try again.", "error");
          }
        },
        error: () => {
          this.showMessage("Search failed. Please try again.", "error");
        },
        complete: () => {
          this.showSearchLoading(false);
        },
      });
    }

    displaySearchResults(results) {
      const $container = $("#posts-container");

      if (results.length === 0) {
        $container.html(`
                    <div class="ch-empty-state">
                        <div class="ch-empty-icon">🔍</div>
                        <h3>No results found</h3>
                        <p>Try different keywords or browse all posts.</p>
                        <button class="ch-btn ch-btn-outline" onclick="location.reload()">
                            Show All Posts
                        </button>
                    </div>
                `);
        return;
      }

      let html = "";
      results.forEach((post) => {
        html += this.createPostCardHTML(post);
      });

      $container.html(html);
      this.addAnimations();
    }

    createPostCardHTML(post) {
      return `
                <article class="ch-post-card" data-post-id="${post.id}">
                    <div class="ch-post-content">
                        <div class="ch-vote-section">
                            <button class="ch-vote-btn" data-vote="up" data-post-id="${post.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m18 15-6-6-6 6"/>
                                </svg>
                            </button>
                            <span class="ch-vote-count">0</span>
                            <button class="ch-vote-btn" data-vote="down" data-post-id="${post.id}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="m6 9 6 6 6-6"/>
                                </svg>
                            </button>
                        </div>
                        <div class="ch-post-details">
                            <div class="ch-post-meta">
                                <span>by u/${post.author}</span>
                                <span>•</span>
                                <span>${post.date} ago</span>
                            </div>
                            <h3 class="ch-post-title">
                                <a href="${post.url}">${post.title}</a>
                            </h3>
                            <div class="ch-post-excerpt">
                                ${post.excerpt}
                            </div>
                            <div class="ch-post-actions">
                                <a href="${post.url}#comments" class="ch-action-btn">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                    </svg>
                                    Comments
                                </a>
                                <button class="ch-action-btn" onclick="sharePost('${post.url}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8M16 6l-4-4-4 4M12 2v13"/>
                                    </svg>
                                    Share
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            `;
    }

    resetSearch() {
      location.reload(); // Simple way to reset to original state
    }

    showSearchLoading(show) {
      const $container = $("#posts-container");
      if (show) {
        $container.html(`
                    <div class="ch-empty-state">
                        <div class="ch-spinner"></div>
                        <h3>Searching...</h3>
                    </div>
                `);
      }
    }

    // Voting system
    initVoting() {
      $(document).on("click", ".ch-vote-btn:not([disabled])", (e) => {
        e.preventDefault();
        const $btn = $(e.currentTarget);
        const postId = $btn.data("post-id");
        const voteType = $btn.data("vote");

        if (!postId) return;

        this.handleVote(postId, voteType, $btn);
      });
    }

    handleVote(postId, voteType, $btn) {
      if (!communityHub.is_logged_in) {
        this.showMessage("Please login to vote", "warning");
        return;
      }

      const $card = $btn.closest(".ch-post-card");
      const $voteCount = $card.find(".ch-vote-count");
      const $upBtn = $card.find('[data-vote="up"]');
      const $downBtn = $card.find('[data-vote="down"]');

      // Show loading
      $btn.addClass("ch-loading");

      $.ajax({
        url: communityHub.ajaxurl,
        type: "POST",
        data: {
          action: "ch_vote_post",
          post_id: postId,
          vote_type: voteType,
          nonce: communityHub.nonce,
        },
        success: (response) => {
          if (response.success) {
            const data = response.data;
            $voteCount.text(data.total);

            // Reset vote classes
            $upBtn.removeClass("voted-up");
            $downBtn.removeClass("voted-down");

            // Apply new vote state
            if (data.user_vote === "up") {
              $upBtn.addClass("voted-up");
            } else if (data.user_vote === "down") {
              $downBtn.addClass("voted-down");
            }

            // Animation
            $voteCount.addClass("ch-fade-in");
            setTimeout(() => $voteCount.removeClass("ch-fade-in"), 300);
          } else {
            this.showMessage(response.data || "Vote failed", "error");
          }
        },
        error: () => {
          this.showMessage("Vote failed. Please try again.", "error");
        },
        complete: () => {
          $btn.removeClass("ch-loading");
        },
      });
    }

    // Sort tabs
    initSortTabs() {
      $(".ch-tab").on("click", (e) => {
        const $tab = $(e.currentTarget);
        const sortType = $tab.data("sort");

        // Update URL and reload
        const url = new URL(window.location);
        url.searchParams.set("sort", sortType);
        window.location.href = url.toString();
      });
    }

    // Load more functionality
    initLoadMore() {
      $("#load-more-posts").on("click", (e) => {
        e.preventDefault();
        const $btn = $(e.currentTarget);

        $btn.addClass("ch-loading").html(`
                    <div class="ch-spinner"></div>
                    Loading...
                `);

        // Simulate loading (replace with actual AJAX call)
        setTimeout(() => {
          $btn.removeClass("ch-loading").html(`
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Load More Posts
                    `);
          this.showMessage("No more posts to load", "info");
        }, 1000);
      });
    }

    // Utility functions
    addAnimations() {
      $(".ch-post-card").each((index, element) => {
        setTimeout(() => {
          $(element).addClass("ch-fade-in");
        }, index * 50);
      });
    }

    showMessage(message, type = "info") {
      // Remove existing messages
      $(".ch-message").remove();

      let icon = "";
      switch (type) {
        case "success":
          icon = "✅";
          break;
        case "error":
          icon = "❌";
          break;
        case "warning":
          icon = "⚠️";
          break;
        default:
          icon = "ℹ️";
      }

      const $message = $(`
                <div class="ch-message ch-message-${type}">
                    <span>${icon}</span>
                    <span>${message}</span>
                    <button class="ch-message-close">×</button>
                </div>
            `);

      $("body").append($message);

      // Auto remove after 5 seconds
      setTimeout(() => {
        $message.fadeOut(() => $message.remove());
      }, 5000);

      // Close button
      $message.find(".ch-message-close").on("click", () => {
        $message.fadeOut(() => $message.remove());
      });
    }

    // Tag filtering
    initTagFiltering() {
      $(document).on("click", ".ch-tag", (e) => {
        e.preventDefault();
        const tag = $(e.currentTarget).data("tag");
        $("#community-search").val(tag).trigger("input");
      });
    }
  }

  // Initialize when document is ready
  $(document).ready(() => {
    const communityHubPro = new CommunityHubPro();

    // Initialize tag filtering
    communityHubPro.initTagFiltering();
  });
})(jQuery);
