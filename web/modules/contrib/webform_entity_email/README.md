# Webform entity email

Provides a webform handler that sends
an email rendering a specific entity. For example: an article.

Use it in cases when you need to send a mail of specific content.
For example: sending an article
to a list of users subscribed to a specific newsletter.

## How it works
The webform needs to have a field
with a reference to the entity that will be rendered in the email.

In the webform handlers page, add an "Entity email webform handler" handler

In the "Entity email" fieldset you can select:

- The entity type.
- The webform field that contains the entity reference.
- The view mode.
- The theme that will be used to render the entity.
  Scheduled emails

## Scheduled emails
It is possible to schedule emails.
To do that, enable the submodule webform_entity_email_scheduled, included with this module.
