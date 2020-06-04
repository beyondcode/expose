---
title: Sites
order: 3
---

# Sites

Expose provides two API endpoints that allow you to either retrieve all currently shared sites, or disconnect a site with a given site ID.

## Retrieving all shared sites

To retrieve the currently shared sites, you can perform a GET request to the `/api/sites` endpoint:

The result looks like this:

```json
{
   "sites":[
      {
         "id":10,
         "host":"beyondco.de.test",
         "client_id":"5ed94b485e2f6",
         "subdomain":"beyondcode",
         "shared_at":"2020-06-04 19:28:08"
      }
   ]
}
```

## Disconnecting a shared site

To disconnect a shared site from your server, you can perform a DELETE request to the `/api/sites/{site_id}` endpoint.