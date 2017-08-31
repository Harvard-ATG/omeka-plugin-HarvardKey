# Omeka Harvard Key Plugin

**HarvardKey** is a plugin that adds a secondary login option so that users can authenticate with the Harvard Key system in addition or instead of a username/password.

### Overview

This plugin does not interface directly with the Harvard Key system via CAS/SAML, but instead redirects users to a service provider that handles that authentication flow with the identity provider. From the perspective of the plugin, a user is authenticated when a signed cookie is provided with identity information. The cryptographic signature is used to ensure the authenticity of the information in the JSON token. The token itself should contain identity information that Omeka can use to create a new account or link to an existing account.

The CAS/SAML service provider is out of scope for this plugin, but is intended to be a common service for multiple Omeka sites. The purpose of the plugin is to faciliate the authentication flow and handle authorization rules that are specific to each site.

### Assumptions

- An authentication provider exists on the same domain as the omeka site (so cookies can be shared). This provider handles the CAS/SAML auth flow with the Harvard Key identity provider.
- The authentication provider will return a signed token (via cookie) with identity information such as _eduPersonPrincipalName_, _mail_, and _displayName_.
- The plugin will be configured with authorization rules that determine whether users are permitted to login or not, and what role/permissions they are assigned.

## Setup

1. Install into the Omeka plugins directory. 
2. Update `auth.ini` settings so that the `url` points to the appropriate login endpoint at the CAS/SAML service provider, and the `secret_key` is the same as the one used by the service provider to sign the identity token returned in the cookie.

