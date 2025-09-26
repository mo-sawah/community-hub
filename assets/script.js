(function ($) {
  "use strict";

  class CommunityHub {
    constructor() {
      this.init();
    }

    init() {
      this.bindEvents();
      this.initTheme();
      this.initSearch();
      this.initCreatePost();
      this.initVoting();
      this.initSortTabs();
    }

    bindEvents() {
      $(document).ready(() => {
        this.onDocumentReady();
      });
    }

    onDocumentReady() {
      console.log("Community Hub initialized");
      this.addAnimations();
    }

    // Theme Management
    initTheme() {
      const $container = $("#community-hub-container");
      const $themeBtn = $("#ch-theme-btn");
      const $sunIcon = $themeBtn.find(".fa-sun");
      const $moonIcon = $themeBtn.find(".fa-moon");

      // Load saved theme
      const savedTheme = localStorage.getItem("ch-theme") || "light";
      if (savedTheme === "dark") {
        $container.addClass("ch-dark-mode");
        $sunIcon.hide();
        $moonIcon.show();
      }

      $themeBtn.on("click", () => {
        $container.toggleClass("ch-dark-mode");
        const isDark = $container.hasClass("ch-dark-mode");
        localStorage.setItem("ch-theme", isDark ? "dark" : "light");

        // Toggle icons
        if (isDark) {
          $sunIcon.hide();
          $moonIcon.show();
        } else {
          $moonIcon.hide();
          $sunIcon.show();
        }

        // Add animation
        $themeBtn.addClass("ch-loading");
        setTimeout(() => $themeBtn.removeClass("ch-loading"), 300);
      });
    }

    // Search Functionality
    initSearch() {
      const $searchInput = $(".ch-search-input");
      let searchTimeout;

      $searchInput.on("input", (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();

        if (query.length < 2) return;

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
      // Filter posts in real-time
      $(".ch-post-card").each(function () {
        const $card = $(this);
        const title = $card.find(".ch-post-title").text().toLowerCase();
        const content = $card.find(".ch-post-excerpt").text().toLowerCase();
        const searchTerm = query.toLowerCase();

        if (title.includes(searchTerm) || content.includes(searchTerm)) {
          $card.show().addClass("ch-fade-in");
        } else {
          $card.hide().removeClass("ch-fade-in");
        }
      });
    }

    // Voting System
    initVoting() {
      $(document).on("click", ".ch-vote-btn:not([disabled])", (e) => {
        e.preventDefault();
        const $btn = $(e.currentTarget);
        const $card = $btn.closest(".ch-post-card");
        const postId = $card.data("post-id");
        const voteType = $btn.data("vote");

        if (!postId) return;

        this.handleVote(postId, voteType, $btn);
      });
    }

    handleVote(postId, voteType, $btn) {
      const $card = $btn.closest(".ch-post-card");
      const $voteCount = $card.find(".ch-vote-count");
      const $upBtn = $card.find('.ch-vote-btn[data-vote="up"]');
      const $downBtn = $card.find('.ch-vote-btn[data-vote="down"]');

      // Show loading
      $btn.addClass("ch-loading");

      $.ajax({
        url: communityAjax.ajaxurl,
        type: "POST",
        data: {
          action: "vote_post",
          post_id: postId,
          vote_type: voteType,
          nonce: communityAjax.nonce,
        },
        success: (response) => {
          const data = JSON.parse(response);
          if (data.total !== undefined) {
            $voteCount.text(data.total);

            // Update UI
            $upBtn.removeClass("ch-voted-up");
            $downBtn.removeClass("ch-voted-down");

            if (
              !$btn.hasClass("ch-voted-up") &&
              !$btn.hasClass("ch-voted-down")
            ) {
              $btn.addClass(
                voteType === "up" ? "ch-voted-up" : "ch-voted-down"
              );
            }

            // Animation
            $voteCount.addClass("ch-fade-in");
            setTimeout(() => $voteCount.removeClass("ch-fade-in"), 300);
          }
        },
        error: (xhr, status, error) => {
          console.error("Vote error:", error);
          this.showMessage("Error voting. Please try again.", "error");
        },
        complete: () => {
          $btn.removeClass("ch-loading");
        },
      });
    }

    // Sort Tabs
    initSortTabs() {
      $(".ch-tab").on("click", (e) => {
        const $tab = $(e.currentTarget);
        const sortType = $tab.data("sort");

        // Update URL
        const url = new URL(window.location);
        url.searchParams.set("sort", sortType);
        window.history.pushState({}, "", url);

        // Reload page with new sort
        window.location.reload();
      });
    }

    // Create Post Functionality
    initCreatePost() {
      if ($("#ch-create-post-form").length === 0) return;

      this.initFormValidation();
      this.initCharacterCounters();
      this.initEditorToolbar();
      this.initPreview();
      this.initFormSubmission();
      this.initDraftLoader();
    }

    initFormValidation() {
      const $form = $("#ch-create-post-form");

      $form.on("submit", (e) => {
        e.preventDefault();
        if (this.validateForm()) {
          this.submitPost();
        }
      });
    }

    validateForm() {
      const title = $("#title").val().trim();
      const content = $("#content").val().trim();
      const community = $("#community").val();

      if (!title) {
        this.showMessage("Please enter a title", "error");
        $("#title").focus();
        return false;
      }

      if (title.length < 5) {
        this.showMessage("Title must be at least 5 characters long", "error");
        $("#title").focus();
        return false;
      }

      if (!content) {
        this.showMessage("Please enter some content", "error");
        $("#content").focus();
        return false;
      }

      if (content.length < 10) {
        this.showMessage(
          "Content must be at least 10 characters long",
          "error"
        );
        $("#content").focus();
        return false;
      }

      if (!community) {
        this.showMessage("Please select a community", "error");
        $("#community").focus();
        return false;
      }

      return true;
    }

    initCharacterCounters() {
      $("#title").on("input", (e) => {
        const length = e.target.value.length;
        $("#title-counter").text(length);

        if (length > 250) {
          $("#title-counter").css("color", "var(--ch-danger)");
        } else if (length > 200) {
          $("#title-counter").css("color", "var(--ch-warning)");
        } else {
          $("#title-counter").css("color", "var(--ch-text-muted)");
        }
      });
    }

    initEditorToolbar() {
      $(".ch-editor-btn").on("click", (e) => {
        const format = $(e.currentTarget).data("format");
        this.formatText(format);
      });
    }

    formatText(format) {
      const $textarea = $("#content");
      const textarea = $textarea[0];
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const selectedText = textarea.value.substring(start, end);

      let replacement = "";
      let cursorOffset = 0;

      switch (format) {
        case "bold":
          replacement = `**${selectedText}**`;
          cursorOffset = selectedText ? 0 : 2;
          break;
        case "italic":
          replacement = `*${selectedText}*`;
          cursorOffset = selectedText ? 0 : 1;
          break;
        case "link":
          replacement = `[${selectedText || "link text"}](url)`;
          cursorOffset = selectedText ? replacement.length - 4 : 1;
          break;
        case "code":
          replacement = `\`${selectedText}\``;
          cursorOffset = selectedText ? 0 : 1;
          break;
        case "list":
          replacement = selectedText
            ? selectedText
                .split("\n")
                .map((line) => `- ${line}`)
                .join("\n")
            : "- List item";
          cursorOffset = selectedText ? 0 : 2;
          break;
        case "quote":
          replacement = selectedText
            ? selectedText
                .split("\n")
                .map((line) => `> ${line}`)
                .join("\n")
            : "> Quote text";
          cursorOffset = selectedText ? 0 : 2;
          break;
      }

      if (replacement) {
        const newValue =
          textarea.value.substring(0, start) +
          replacement +
          textarea.value.substring(end);
        $textarea.val(newValue);

        // Set cursor position
        const newPos = start + replacement.length - cursorOffset;
        textarea.setSelectionRange(newPos, newPos);
        $textarea.focus();
      }
    }

    initPreview() {
      $("#preview-btn").on("click", () => {
        this.showPreview();
      });

      $("#close-preview").on("click", () => {
        $("#preview-modal").hide();
      });

      $(document).on("click", (e) => {
        if ($(e.target).hasClass("ch-modal")) {
          $("#preview-modal").hide();
        }
      });
    }

    showPreview() {
      const title = $("#title").val();
      const content = $("#content").val();
      const community = $("#community option:selected").text();
      const postType = $('input[name="post_type"]:checked').val();
      const tags = $("#tags").val();

      let typeIcon = "";
      switch (postType) {
        case "question":
          typeIcon = '<i class="fas fa-question-circle"></i>';
          break;
        case "tutorial":
          typeIcon = '<i class="fas fa-graduation-cap"></i>';
          break;
        case "announcement":
          typeIcon = '<i class="fas fa-bullhorn"></i>';
          break;
        default:
          typeIcon = '<i class="fas fa-comments"></i>';
      }

      const previewHtml = `
                <div class="ch-post-card">
                    <div class="ch-post-content">
                       <div class="ch-vote-section">
                            <button class="ch-vote-btn">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <span class="ch-vote-count">1</span>
                            <button class="ch-vote-btn">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="ch-post-details">
                            <div class="ch-post-meta">
                                <span class="ch-community">
                                    <i class="fas fa-tag"></i>
                                    ${community}
                                </span>
                                <span>•</span>
                                <span>
                                    <i class="fas fa-user"></i>
                                    by u/you
                                </span>
                                <span>•</span>
                                <span>
                                    <i class="fas fa-clock"></i>
                                    just now
                                </span>
                            </div>
                            <h3 class="ch-post-title">
                                ${typeIcon} ${title}
                            </h3>
                            <div class="ch-post-excerpt">
                                ${content.substring(0, 200)}${
        content.length > 200 ? "..." : ""
      }
                            </div>
                            ${
                              tags
                                ? `<div class="ch-post-tags">
                                ${tags
                                  .split(",")
                                  .map(
                                    (tag) =>
                                      `<span class="ch-tag">${tag.trim()}</span>`
                                  )
                                  .join("")}
                            </div>`
                                : ""
                            }
                            <div class="ch-post-actions">
                                <button class="ch-action-btn">
                                    <i class="fas fa-comment"></i>
                                    0 comments
                                </button>
                                <button class="ch-action-btn">
                                    <i class="fas fa-share"></i>
                                    Share
                                </button>
                                <button class="ch-action-btn">
                                    <i class="fas fa-bookmark"></i>
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      $("#preview-content").html(previewHtml);
      $("#preview-modal").show().addClass("ch-fade-in");
    }

    initFormSubmission() {
      $("#save-draft-btn").on("click", () => {
        this.saveDraft();
      });
    }

    submitPost() {
      const $form = $("#ch-create-post-form");
      const $submitBtn = $("#publish-btn");

      $submitBtn
        .addClass("ch-loading")
        .html('<i class="fas fa-spinner fa-spin"></i> Publishing...');

      const formData = {
        action: "create_post",
        title: $("#title").val(),
        content: $("#content").val(),
        community: $("#community").val(),
        tags: $("#tags").val(),
        post_type: $('input[name="post_type"]:checked').val(),
        nonce: $('input[name="nonce"]').val(),
      };

      $.ajax({
        url: communityAjax.ajaxurl,
        type: "POST",
        data: formData,
        success: (response) => {
          const data = JSON.parse(response);
          if (data.success) {
            this.showMessage("Post published successfully!", "success");
            // Clear draft
            localStorage.removeItem("ch_draft");
            setTimeout(() => {
              window.location.href = window.location.origin + "/forum/";
            }, 1500);
          } else {
            this.showMessage("Error creating post. Please try again.", "error");
          }
        },
        error: () => {
          this.showMessage("Error creating post. Please try again.", "error");
        },
        complete: () => {
          $submitBtn
            .removeClass("ch-loading")
            .html('<i class="fas fa-paper-plane"></i> Publish Post');
        },
      });
    }

    saveDraft() {
      const draftData = {
        title: $("#title").val(),
        content: $("#content").val(),
        community: $("#community").val(),
        tags: $("#tags").val(),
        post_type: $('input[name="post_type"]:checked').val(),
        timestamp: new Date().toISOString(),
      };

      localStorage.setItem("ch_draft", JSON.stringify(draftData));
      this.showMessage("Draft saved!", "success");
    }

    loadDraft() {
      const draft = localStorage.getItem("ch_draft");
      if (draft) {
        const data = JSON.parse(draft);
        $("#title").val(data.title || "");
        $("#content").val(data.content || "");
        $("#community").val(data.community || "");
        $("#tags").val(data.tags || "");
        if (data.post_type) {
          $(`input[name="post_type"][value="${data.post_type}"]`).prop(
            "checked",
            true
          );
        }

        // Update character counter
        $("#title").trigger("input");

        this.showMessage("Draft loaded!", "info");
      }
    }

    initDraftLoader() {
      if (window.location.pathname.includes("create-post")) {
        // Check for draft on page load
        const draft = localStorage.getItem("ch_draft");
        if (draft) {
          const data = JSON.parse(draft);
          const age = (new Date() - new Date(data.timestamp)) / (1000 * 60); // minutes

          if (age < 60) {
            // Less than 1 hour old
            if (
              confirm("You have a recent draft. Would you like to load it?")
            ) {
              this.loadDraft();
            }
          }
        }

        this.initAutoSave();
      }
    }

    initAutoSave() {
      let autoSaveTimeout;

      $('#title, #content, #community, #tags, input[name="post_type"]').on(
        "input change",
        () => {
          clearTimeout(autoSaveTimeout);
          autoSaveTimeout = setTimeout(() => {
            this.saveDraft();
          }, 30000); // Auto-save every 30 seconds
        }
      );
    }

    // Utility Functions
    addAnimations() {
      $(".ch-post-card").each((index, element) => {
        setTimeout(() => {
          $(element).addClass("ch-slide-up");
        }, index * 50);
      });
    }

    showMessage(message, type = "info") {
      // Remove existing messages
      $(".ch-message").remove();

      let icon = "";
      switch (type) {
        case "success":
          icon = '<i class="fas fa-check-circle"></i>';
          break;
        case "error":
          icon = '<i class="fas fa-exclamation-circle"></i>';
          break;
        case "warning":
          icon = '<i class="fas fa-exclamation-triangle"></i>';
          break;
        default:
          icon = '<i class="fas fa-info-circle"></i>';
      }

      const $message = $(`
                <div class="ch-message ch-message-${type}">
                    ${icon}
                    <span>${message}</span>
                    <button class="ch-message-close">
                        <i class="fas fa-times"></i>
                    </button>
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

    // Load More Posts
    initLoadMore() {
      $("#load-more-posts").on("click", (e) => {
        e.preventDefault();
        const $btn = $(e.currentTarget);
        $btn
          .addClass("ch-loading")
          .html('<i class="fas fa-spinner fa-spin"></i> Loading...');

        // Simulate loading more posts
        setTimeout(() => {
          $btn
            .removeClass("ch-loading")
            .html('<i class="fas fa-plus"></i> Load More Posts');
          this.showMessage("No more posts to load", "info");
        }, 1000);
      });
    }

    // Copy to clipboard utility
    copyToClipboard(text) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          this.showMessage("Link copied to clipboard!", "success");
        });
      } else {
        // Fallback
        const textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand("copy");
        document.body.removeChild(textArea);
        this.showMessage("Link copied to clipboard!", "success");
      }
    }
  }

  // Global function for share button
  window.copyToClipboard = function (text) {
    const hub = new CommunityHub();
    hub.copyToClipboard(text);
  };

  // Initialize the plugin
  const communityHub = new CommunityHub();

  // Initialize load more if present
  if ($("#load-more-posts").length) {
    communityHub.initLoadMore();
  }
})(jQuery);
