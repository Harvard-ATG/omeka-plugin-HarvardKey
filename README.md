# Omeka Harvard Key Plugin

Authenticate with Harvard Key credentials provided in the form of a signed cookie from an identity proxy on the same domain.

## How it works

1. User loads the login page on the Omeka site.
2. User chooses to authenticate with Harvard Key.
3. User is redirected to the identity proxy on the same domain.
4. Identity proxy redirects to the Harvard Key system.
5. Harvard Key system redirects back to the identity proxy.
6. Identity proxy sets a signed cookie with credentials that any site on the domain can consume.
7. Identity proxy redirects back to the Omeka site.
8. Omeka site authenticates and validates the signed cookie and logs the user in.

## Setup

1. Install into the Omeka plugins directory. 
2. Update `auth.ini` and update the *secret_key* and *url* to the identity provider. 

