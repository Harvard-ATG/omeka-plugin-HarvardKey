# Omeka Harvard Key Plugin

**HarvardKey** is a plugin that adds a login option so that users can authenticate with the Harvard Key system in addition to logging in with a username and password.

![Login Screen](https://raw.githubusercontent.com/Harvard-ATG/omeka-plugin-HarvardKey/master/doc/loginscreen.png "Login Screen")

## Setup

1. Install into the Omeka plugins directory. 
2. Update `auth.ini` settings so that the `url` points to the appropriate login endpoint at the CAS/SAML service provider, and the `secret_key` is the same as the one used by the service provider to sign the identity token returned in the cookie.

## Description

This plugin does not use the CAS/SAML protocol directly, but instead interfaces with an intermediate auth service provider which handles that part of the authentication process. The auth service provider simply returns a cryptographically signed cookie (JSON format) with identity information including name, email, and ID (the eduPersonPrincipalName) to the plugin. 

Omeka can use the identity information to create a new account or link to an existing account based on the email address.

**Assumptions**:
- An auth service provider (SP) exists on the same domain as the omeka site so that cookies can be shared. The SP handles the CAS/SAML protocol to authenticate with the Harvard Key identity provider.
- The auth service provider (SP) returns a signed token in JSON format via cookie. The token contains identity information such as _id_ (_eduPersonPrincipalName_), _mail_, and _displayName_. 
- The plugin is configured with authorization rules that determine whether users are permitted to login (allow/deny) and if they are allowed to login, what role/permissions they receive.

## Diagram

The diagram below shows the high-level flow of requests between the user agent (browser), the omeka site containing protected resources, the auth service provider for the omeka site, and the Harvard Key identity provider.

![Diagram](https://raw.githubusercontent.com/Harvard-ATG/omeka-plugin-HarvardKey/master/doc/diagram.png "Diagram")
