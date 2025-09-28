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
      this.initForms();
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
          <div class="chp-empty-state">
            <div class="chp-empty-icon">🔍</div>
            <h3>No results found</h3>
            <p>Try different keywords or browse all posts.</p>
            <button class="chp-btn chp-btn-outline" onclick="location.reload()">
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
        <article class="chp-post-card" data-post-id="${post.id}">
          <div class="chp-post-content">
            <div class="chp-vote-section">
              <button class="chp-vote-btn" data-vote="up" data-post-id="${post.id}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m18 15-6-6-6 6"/>
                </svg>
              </button>
              <span class="chp-vote-count">0</span>
              <button class="chp-vote-btn" data-vote="down" data-post-id="${post.id}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="m6 9 6 6 6-6"/>
                </svg>
              </button>
            </div>
            <div class="chp-post-details">
              <div class="chp-post-meta">
                <span>by u/${post.author}</span>
                <span>•</span>
                <span>${post.date} ago</span>
              </div>
              <h3 class="chp-post-title">
                <a href="${post.url}">${post.title}</a>
              </h3>
              <div class="chp-post-excerpt">
                ${post.excerpt}
              </div>
              <div class="chp-post-actions">
                <a href="${post.url}#comments" class="chp-action-btn">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                  </svg>
                  Comments
                </a>
                <button class="chp-action-btn" onclick="sharePost('${post.url}')">
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
          <div class="chp-empty-state">
            <div class="chp-spinner"></div>
            <h3>Searching...</h3>
          </div>
        `);
      }
    }

    // Voting system
    initVoting() {
      $(document).on("click", ".chp-vote-btn:not([disabled])", (e) => {
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

      const $card = $btn.closest(".chp-post-card, .chp-single-post");
      const $voteCount = $card.find(".chp-vote-count");
      const $upBtn = $card.find('[data-vote="up"]');
      const $downBtn = $card.find('[data-vote="down"]');

      // Show loading
      $btn.addClass("chp-loading");

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
            $voteCount.addClass("chp-fade-in");
            setTimeout(() => $voteCount.removeClass("chp-fade-in"), 300);
          } else {
            this.showMessage(response.data || "Vote failed", "error");
          }
        },
        error: () => {
          this.showMessage("Vote failed. Please try again.", "error");
        },
        complete: () => {
          $btn.removeClass("chp-loading");
        },
      });
    }

    // Sort tabs
    initSortTabs() {
      $(".chp-tab").on("click", (e) => {
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

        $btn.addClass("chp-loading").html(`
          <div class="chp-spinner"></div>
          Loading...
        `);

        // Simulate loading (replace with actual AJAX call)
        setTimeout(() => {
          $btn.removeClass("chp-loading").html(`
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            Load More Posts
          `);
          this.showMessage("No more posts to load", "info");
        }, 1000);
      });
    }

    // Form handling
    initForms() {
      // Character counter for title
      $("#title").on("input", function () {
        $("#title-counter").text(this.value.length);
      });

      // Create post form
      $("#chp-create-post-form").on("submit", (e) => {
        e.preventDefault();
        this.handleCreatePost();
      });

      // Comment form
      $("#comment-form").on("submit", (e) => {
        e.preventDefault();
        this.handleAddComment();
      });

      // Preview functionality
      $("#preview-btn").on("click", () => {
        this.showPreview();
      });

      $("#close-preview").on("click", () => {
        $("#preview-modal").hide();
      });

      // Close modal on outside click
      $("#preview-modal").on("click", (e) => {
        if (e.target === e.currentTarget) {
          $(e.currentTarget).hide();
        }
      });
    }

    handleCreatePost() {
      const $form = $("#chp-create-post-form");
      const $btn = $("#publish-btn");

      $btn
        .addClass("chp-loading")
        .html('<div class="chp-spinner"></div> Publishing...');

      $.ajax({
        url: communityHub.ajaxurl,
        type: "POST",
        data: $form.serialize() + "&action=ch_create_post",
        success: (response) => {
          if (response.success) {
            window.location.href = response.data.redirect;
          } else {
            this.showMessage(response.data || "Failed to create post", "error");
          }
        },
        error: () => {
          this.showMessage("Failed to create post. Please try again.", "error");
        },
        complete: () => {
          $btn.removeClass("chp-loading").html("<span>🚀</span> Publish Post");
        },
      });
    }

    handleAddComment() {
      const content = $("#comment-content").val().trim();
      if (!content) {
        this.showMessage("Please enter a comment", "warning");
        return;
      }

      const $btn = $("#comment-form button[type='submit']");
      $btn
        .addClass("chp-loading")
        .html('<div class="chp-spinner"></div> Posting...');

      $.ajax({
        url: communityHub.ajaxurl,
        type: "POST",
        data: {
          action: "ch_add_comment",
          post_id: $("article.chp-single-post").data("post-id"),
          content: content,
          parent_id: 0,
          nonce: communityHub.nonce,
        },
        success: (response) => {
          if (response.success) {
            $("#comment-content").val("");
            $(".chp-comments-list").prepend(response.data.html);
            this.showMessage("Comment posted successfully!", "success");
          } else {
            this.showMessage(
              response.data || "Failed to post comment",
              "error"
            );
          }
        },
        error: () => {
          this.showMessage(
            "Failed to post comment. Please try again.",
            "error"
          );
        },
        complete: () => {
          $btn.removeClass("chp-loading").html("Comment");
        },
      });
    }

    showPreview() {
      const title = $("#title").val();
      const content = $("#content").val();
      const community = $("#community option:selected").text();

      if (!title || !content) {
        this.showMessage(
          "Please fill in title and content to preview",
          "warning"
        );
        return;
      }

      $("#preview-content").html(`
        <div style="border-bottom: 1px solid var(--chp-border); padding-bottom: 16px; margin-bottom: 16px;">
          <div style="font-size: 12px; color: var(--chp-text-muted); margin-bottom: 8px;">
            ${community} • by u/${
        communityHub.current_user || "user"
      } • just now
          </div>
          <h2 style="font-size: 20px; font-weight: 600; margin-bottom: 12px; color: var(--chp-text-primary);">${title}</h2>
        </div>
        <div style="line-height: 1.6; color: var(--chp-text-primary);">${content.replace(
          /\n/g,
          "<br>"
        )}</div>
      `);
      $("#preview-modal").show();
    }

    // Utility functions
    addAnimations() {
      $(".chp-post-card").each((index, element) => {
        setTimeout(() => {
          $(element).addClass("chp-fade-in");
        }, index * 50);
      });
    }

    showMessage(message, type = "info") {
      // Remove existing messages
      $(".chp-message").remove();

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
        <div class="chp-message chp-message-${type}">
          <span>${icon}</span>
          <span>${message}</span>
          <button class="chp-message-close">×</button>
        </div>
      `);

      $("body").append($message);

      // Auto remove after 5 seconds
      setTimeout(() => {
        $message.fadeOut(() => $message.remove());
      }, 5000);

      // Close button
      $message.find(".chp-message-close").on("click", () => {
        $message.fadeOut(() => $message.remove());
      });
    }

    // Tag filtering
    initTagFiltering() {
      $(document).on("click", ".chp-tag", (e) => {
        e.preventDefault();
        const tag = $(e.currentTarget).data("tag");
        $("#community-search").val(tag).trigger("input");
      });
    }
  }

  // Initialize when document is ready
  $(document).ready(() => {
    const communityHubPro = new CommunityHubPro();
    communityHubPro.initTagFiltering();
  });

  // Global functions for post interactions
  window.sharePost = function (url) {
    if (navigator.share) {
      navigator.share({
        title: "Check out this post",
        url: url,
      });
    } else {
      navigator.clipboard
        .writeText(url)
        .then(() => {
          $("body").trigger("showMessage", [
            "Link copied to clipboard!",
            "success",
          ]);
        })
        .catch(() => {
          const textArea = document.createElement("textarea");
          textArea.value = url;
          document.body.appendChild(textArea);
          textArea.select();
          document.execCommand("copy");
          document.body.removeChild(textArea);
          $("body").trigger("showMessage", [
            "Link copied to clipboard!",
            "success",
          ]);
        });
    }
  };

  window.savePost = function (postId) {
    $("body").trigger("showMessage", ["Post saved!", "success"]);
  };
})(jQuery);
