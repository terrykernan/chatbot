=== Woo Chatbot ===
Contributors: terrykernan
Tags: chatbot, ai, woocommerce, claude, openai
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered chat widget for WooCommerce stores. Works with Claude or OpenAI, answers from your own knowledge base, and hands off to a human by email.

== Description ==

Woo Chatbot adds a floating chat widget to every page of your store. Visitors ask questions about products, coverage, delivery and so on, and the assistant answers using only the knowledge base you maintain in WordPress admin. When a visitor asks for a person, or the answer isn't in the knowledge base, the conversation is emailed to your team.

**Features**

* **Choose your AI provider:** Anthropic Claude (Haiku or Sonnet) or OpenAI (GPT-4o Mini or GPT-4o). Switch at any time from the settings page.
* **Answers from your knowledge base:** the assistant is told to answer only from the text you provide and not to invent prices, delivery times or product names.
* **Human handoff:** when a visitor asks for a person, the conversation is marked as handed off and the full transcript is emailed to an address you choose.
* **Conversation log:** every conversation is stored in your database. View transcripts, mark them resolved or delete them from the Conversations screen.
* **Dashboard:** see conversation and handoff activity at a glance.
* **Custom widget:** set the assistant's name, greeting message and accent colour.
* **Encrypted API keys:** keys are stored encrypted (AES-256) using your site's `AUTH_KEY`, and masked in the admin screens.
* **Rate limiting:** each IP address can send up to 30 messages every 10 minutes.
* **Prompt caching:** with Claude, the system prompt and knowledge base are cached to lower token costs on repeat requests.

== Installation ==

1. Upload the `chatbot` folder to `/wp-content/plugins/`, or install the zip from **Plugins > Add New > Upload Plugin**.
2. Activate the plugin from the **Plugins** screen. Activation creates two database tables and sets default options.
3. Go to **Woo Chatbot > Settings**:
    * Pick a provider (Claude or OpenAI).
    * Paste your API key and choose a model.
    * Set the store name, handoff email and how many previous messages to send as context.
4. Go to **Woo Chatbot > Knowledge Base** and enter the information the assistant should answer from: products, pricing, delivery, returns, opening hours and so on. On first activation this is filled from the bundled `knowledge.md`.
5. Customise the widget name, greeting and colour on the Settings screen.
6. Visit your site's front end. The chat widget appears in the corner of every page.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes. You need an API key from Anthropic (https://console.anthropic.com/) or OpenAI (https://platform.openai.com/). Usage is billed by the provider to your account.

= Which model should I use? =

The cheapest models (Claude Haiku or GPT-4o Mini) handle most store questions well. Switch to Claude Sonnet or GPT-4o if answers need more reasoning.

= Will the bot make things up? =

It is instructed to answer only from your knowledge base and to offer a human handoff when it doesn't know. Keep the knowledge base complete and up to date for the best results.

= How does human handoff work? =

If a visitor asks to speak to a person, the assistant flags the conversation, shows the visitor a confirmation, and emails the full transcript with a link to the conversation in admin. The email goes to the **Handoff Email** address in Settings, which defaults to the site admin email.

= Where are conversations stored? =

In two custom tables, `{prefix}pc_conversations` and `{prefix}pc_messages`, in your WordPress database. The visitor's IP address and user agent are stored with each conversation.

= What happens when I delete the plugin? =

Deactivating keeps all data. Deleting the plugin from the Plugins screen drops both tables, removes all plugin options (including API keys and the knowledge base) and clears rate-limit transients.

= Is there a REST endpoint? =

Yes. The widget posts to `/wp-json/woo-chatbot/v1/chat` with a `session_id` (UUID) and `message`. Requests need a valid `wp_rest` nonce and are rate limited.

== External services ==

This plugin sends chat messages to a third-party AI service to generate replies. Only the provider selected in Settings is contacted, and only when a visitor sends a message.

**Data sent:** the system prompt (including your store name and knowledge base), the visitor's message, and up to the configured number of previous messages in the conversation. No WordPress user data is sent.

* **Anthropic Claude API** (`https://api.anthropic.com/v1/messages`), used when the provider is set to Claude.
  [Terms of Service](https://www.anthropic.com/legal/commercial-terms) | [Privacy Policy](https://www.anthropic.com/legal/privacy)
* **OpenAI API** (`https://api.openai.com/v1/chat/completions`), used when the provider is set to OpenAI.
  [Terms of Use](https://openai.com/policies/terms-of-use) | [Privacy Policy](https://openai.com/policies/privacy-policy)

You should tell visitors about this in your site's privacy policy.

== Screenshots ==

1. Chat widget on the storefront.
2. Dashboard with conversation stats.
3. Knowledge Base editor.
4. Conversations list and transcript view.
5. Settings: provider, API key, model and widget appearance.

== Changelog ==

= 1.0.0 =
* Initial release.
* Claude and OpenAI providers.
* Knowledge base, conversation log, dashboard and settings screens.
* Email handoff with transcript.
* Encrypted API key storage and per-IP rate limiting.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
