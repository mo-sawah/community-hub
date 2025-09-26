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

      // Load saved theme
      const savedTheme = localStorage.getItem("ch-theme") || "light";
      if (savedTheme === "dark") {
        $container.addClass("ch-dark-mode");
      }

      $themeBtn.on("click", () => {
        $container.toggleClass("ch-dark-mode");
        const isDark = $container.hasClass("ch-dark-mode");
        localStorage.setItem("ch-theme", isDark ? "dark" : "light");

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
      // Implement search logic here
      // This would filter posts in real-time or redirect to search results
    }

    // Voting System
    initVoting() {
      $(document).on("click", ".ch-vote-btn", (e) => {
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
      const $upBtn = $card.find(".ch-vote-up");
      const $downBtn = $card.find(".ch-vote-down");

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
            $upBtn.removeClass("ch-voted");
            $downBtn.removeClass("ch-voted");

            if (!$btn.hasClass("ch-voted")) {
              $btn.addClass("ch-voted");
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

        // Update active tab
        $(".ch-tab").removeClass("ch-tab-active");
        $tab.addClass("ch-tab-active");

        // Sort posts
        this.sortPosts(sortType);
      });
    }

    sortPosts(sortType) {
      const $container = $(".ch-posts-container");
      const $posts = $container.find(".ch-post-card");

      $container.addClass("ch-loading");

      // Simulate sorting delay
      setTimeout(() => {
        console.log("Sorting by:", sortType);
        // Implement actual sorting logic here
        $container.removeClass("ch-loading");
      }, 500);
    }

    // Create Post Functionality
    initCreatePost() {
      this.initFormValidation();
      this.initCharacterCounters();
      this.initEditorToolbar();
      this.initPreview();
      this.initFormSubmission();
      this.initAIGeneration();
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

      if (!content) {
        this.showMessage("Please enter some content", "error");
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
      switch (format) {
        case "bold":
          replacement = `**${selectedText}**`;
          break;
        case "italic":
          replacement = `*${selectedText}*`;
          break;
        case "link":
          replacement = `[${selectedText}](url)`;
          break;
        case "code":
          replacement = `\`${selectedText}\``;
          break;
      }

      if (replacement) {
        const newValue =
          textarea.value.substring(0, start) +
          replacement +
          textarea.value.substring(end);
        $textarea.val(newValue);

        // Set cursor position
        const newPos = start + replacement.length;
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

      const previewHtml = `
                <div class="ch-post-card">
                    <div class="ch-post-content">
                        <div class="ch-vote-section">
                            <button class="ch-vote-btn">⬆</button>
                            <span class="ch-vote-count">1</span>
                            <button class="ch-vote-btn">⬇</button>
                        </div>
                        <div class="ch-post-details">
                            <div class="ch-post-meta">
                                <span class="ch-community">${community}</span>
                                <span class="ch-author">by u/you</span>
                                <span class="ch-time">just now</span>
                            </div>
                            <h3 class="ch-post-title">${title}</h3>
                            <div class="ch-post-excerpt">${content}</div>
                            <div class="ch-post-actions">
                                <button class="ch-action-btn">💬 0 comments</button>
                                <button class="ch-action-btn">📤 Share</button>
                                <button class="ch-action-btn">🚩 Report</button>
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

      $submitBtn.addClass("ch-loading").text("Publishing...");

      const formData = {
        action: "create_post",
        title: $("#title").val(),
        content: $("#content").val(),
        community: $("#community").val(),
        tags: $("#tags").val(),
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
          $submitBtn.removeClass("ch-loading").text("🚀 Publish Post");
        },
      });
    }

    saveDraft() {
      const draftData = {
        title: $("#title").val(),
        content: $("#content").val(),
        community: $("#community").val(),
        tags: $("#tags").val(),
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
      }
    }

    // AI Generation
    initAIGeneration() {
      $("#ai-generate-btn").on("click", () => {
        this.generateAIContent();
      });
    }

    generateAIContent() {
      const title = $("#title").val();
      const community = $("#community option:selected").text();

      if (!title) {
        this.showMessage(
          "Please enter a title first to generate content",
          "error"
        );
        return;
      }

      const $btn = $("#ai-generate-btn");
      $btn.addClass("ch-loading").text("✨ Generating...");

      // Simulate AI generation (replace with actual OpenRouter API call)
      setTimeout(() => {
        const generatedContent = this.simulateAIGeneration(title, community);
        $("#content").val(generatedContent);
        $btn.removeClass("ch-loading").text("✨ AI Generate");
        this.showMessage("Content generated successfully!", "success");
      }, 2000);
    }

    simulateAIGeneration(title, community) {
      // This would be replaced with actual OpenRouter API call
      const templates = {
        announcements: `This is an exciting announcement about ${title}. 

We're thrilled to share this update with our community. Here are the key highlights:

- Important update regarding our platform
- New features and improvements
- What this means for our users
- Timeline and next steps

We appreciate your continued support and feedback. Please feel free to share your thoughts in the comments below.`,

        development: `Here's a comprehensive discussion about ${title}:

## Overview
This topic covers important aspects of development that every developer should know.

## Key Points
- Technical considerations
- Best practices and patterns
- Common pitfalls to avoid
- Implementation strategies

## Code Example
\`\`\`javascript
// Example implementation
function example() {
    return "This is a sample";
}
\`\`\`

## Conclusion
What are your thoughts on this approach? I'd love to hear your experiences and suggestions.`,

        "feature-requests": `I'd like to propose a new feature: ${title}

## Problem Statement
Currently, users face challenges with...

## Proposed Solution
This feature would help by providing...

## Benefits
- Improved user experience
- Better workflow efficiency
- Enhanced functionality
- Reduced friction

## Implementation Ideas
Here are some thoughts on how this could work...

What do you think? Would this be valuable for the community?`,

        default: `Let's discuss ${title}.

This is an important topic that deserves our attention. Here are some key points to consider:

- Main aspects to discuss
- Different perspectives to explore
- Potential solutions or approaches
- Community input and feedback

I'm curious to hear what others think about this. Please share your experiences and insights!`,
      };

      const communityKey = community.toLowerCase().replace("r/", "");
      return templates[communityKey] || templates["default"];
    }

    // Utility Functions
    addAnimations() {
      $(".ch-post-card").each((index, element) => {
        setTimeout(() => {
          $(element).addClass("ch-slide-up");
        }, index * 100);
      });
    }

    showMessage(message, type = "info") {
      // Remove existing messages
      $(".ch-message").remove();

      const $message = $(`
                <div class="ch-message ch-message-${type}">
                    <span>${message}</span>
                    <button class="ch-message-close">&times;</button>
                </div>
            `);

      $message.css({
        position: "fixed",
        top: "20px",
        right: "20px",
        padding: "1rem 1.5rem",
        borderRadius: "var(--ch-radius)",
        color: "white",
        fontWeight: "500",
        zIndex: "1001",
        display: "flex",
        alignItems: "center",
        gap: "1rem",
        minWidth: "300px",
        maxWidth: "500px",
        boxShadow: "var(--ch-shadow-lg)",
        animation: "ch-slideUp 0.3s ease-out",
      });

      if (type === "success") {
        $message.css("background", "var(--ch-success)");
      } else if (type === "error") {
        $message.css("background", "var(--ch-danger)");
      } else if (type === "warning") {
        $message.css("background", "var(--ch-warning)");
      } else {
        $message.css("background", "var(--ch-primary)");
      }

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

    // API Integration (OpenRouter)
    async callOpenRouterAPI(prompt, options = {}) {
      const settings = await this.getPluginSettings();

      if (!settings.openrouter_api_key) {
        this.showMessage("OpenRouter API key not configured", "error");
        return null;
      }

      try {
        const response = await fetch(
          "https://openrouter.ai/api/v1/chat/completions",
          {
            method: "POST",
            headers: {
              Authorization: `Bearer ${settings.openrouter_api_key}`,
              "Content-Type": "application/json",
              "X-Title": "Community Hub Plugin",
            },
            body: JSON.stringify({
              model: options.model || "anthropic/claude-3-sonnet",
              messages: [
                {
                  role: "user",
                  content: prompt,
                },
              ],
              max_tokens: options.max_tokens || 1000,
              temperature: options.temperature || 0.7,
            }),
          }
        );

        if (!response.ok) {
          throw new Error(`API request failed: ${response.status}`);
        }

        const data = await response.json();
        return data.choices[0]?.message?.content;
      } catch (error) {
        console.error("OpenRouter API error:", error);
        this.showMessage("AI generation failed. Please try again.", "error");
        return null;
      }
    }

    async getPluginSettings() {
      return new Promise((resolve) => {
        $.ajax({
          url: communityAjax.ajaxurl,
          type: "POST",
          data: {
            action: "get_plugin_settings",
            nonce: communityAjax.nonce,
          },
          success: (response) => {
            resolve(JSON.parse(response));
          },
          error: () => {
            resolve({});
          },
        });
      });
    }

    // Auto-generate content with AI
    async generateRealAIContent() {
      const title = $("#title").val();
      const community = $("#community option:selected").text();

      const prompt = `Create engaging forum post content for a post titled "${title}" in the ${community} community. 
            The content should be:
            - Informative and engaging
            - Appropriate for the community topic
            - Well-structured with clear sections
            - Include relevant examples or code if technical
            - Encourage community discussion
            - Be around 200-400 words
            
            Please write in a conversational tone that encourages responses from community members.`;

      const content = await this.callOpenRouterAPI(prompt);
      if (content) {
        $("#content").val(content);
        this.showMessage("AI content generated successfully!", "success");
      }
    }

    // Initialize auto-save
    initAutoSave() {
      let autoSaveTimeout;

      $("#title, #content, #community, #tags").on("input change", () => {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(() => {
          this.saveDraft();
        }, 30000); // Auto-save every 30 seconds
      });
    }

    // Load draft on page load
    initDraftLoader() {
      if (window.location.pathname.includes("create-post")) {
        this.loadDraft();
        this.initAutoSave();
      }
    }
  }

  // Initialize the plugin
  const communityHub = new CommunityHub();
})(jQuery);
