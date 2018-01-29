# envoysir
API Web-form Wrapper + Invoice Generator

## Overview

### Views
[**Google Sheet**](https://docs.google.com/spreadsheets/d/1GeM5KaL97qNLKdh8jbf0kFfRyOlJNwJiHgZn_S04gVo/edit?usp=sharing#gid=0) provides primary view / interface for contractor to request data. 

Google Script actions supply additional menu options (see below) to pull data via the API wrapper into the [CSV Import Sheet](https://docs.google.com/spreadsheets/d/1GeM5KaL97qNLKdh8jbf0kFfRyOlJNwJiHgZn_S04gVo/edit?usp=sharing#gid=409517716). 
![Custom Functions](/instructions/0.%20Functions.PNG)

[Generic style and formatting](https://docs.google.com/spreadsheets/d/1GeM5KaL97qNLKdh8jbf0kFfRyOlJNwJiHgZn_S04gVo/edit?usp=sharing#gid=1016718603), along with further decoration from a proprietary pricelist, are applied to create a view that can be exported to PDF via print, and emailed to the client. 

Contractors could further [customize their invoice](https://docs.google.com/spreadsheets/d/1GeM5KaL97qNLKdh8jbf0kFfRyOlJNwJiHgZn_S04gVo/edit?usp=sharing#gid=793291208) with a "style" view. *Sharp Retention*'s was built as an example; by saving the sheet separately, it could be imported to updated copies of the master sheet allowing for contractors to save their style without missing out on updates to functionality.


### Controller

[Google Scripts](/script.google.com) generate the menu actions which interface with the PHP API (not currently in production). 

The PHP API uses the information from the Google Sheet to interact with [a webform](https://www.cutco.com/customer/orderStatus.jsp) to:
- POST to a form endpoint the information necessary to get the order information
- scrape the HTML response
- convert the full HTML response into a pipe-delimited list (CSV) of the relevant values
- handle errors if something unexpected happened
- ensure that the order was associated with a contractor that was paying to use the service
- cache the result to minimize impact on the Cutco.com form endpoint

The API would also perform additional operations on the order data, depending on GET parameter flags: One common request was to bundle and simplify line items as much as possible: e.g., when an individual piece would be given as a gift with one SKU, it would need a gift box with a separate SKU, so the invoice line item should show the item, box included, with the total price. This sound simpler than it is. (this feature in particular is implemented by [box compactor](boxcom.php))


### Model

Database usage was minimal, primarily consisting of a table of users and their subscription state, and a log of usage / attempts. 


## Status

This project was put on hiatus indefinitely in early 2016 after I secured full-time employment, as the time and effort of a) addressing issues / feature requests and b) billing users became untenable.

Prior to hiatus, the most requested feature was quote generation, which would have shifted the role of the app to become the initiation point of the order, allowing for syndication of specialized data subsets to all the various stakeholders. (product fulfillment, engraving services, contractor CRM, client invoice, etc.) Progress towards that end was made on various fronts prior to project stalling. Other goals for the project prior to hiatus included moving to a stand-alone homegrown UI, which would have allowed for a library of applicable invoice styles, and automation of additional bundling which could be adjusted by the user. 


## Background  
Cutco / Vector Reps, as contractors, have flexibility in the degree of professionalism they leverage in the course of running their business. One particular subset of Cutco representatives creatively identified a new vertical market: serving real estate agents by providing engraved closing gifts. Among many advantages to popular alternatives, [Cutco Closing Gifts](https://www.cutcoclosinggifts.com/) are uniquely qualified as a **fully** deductible marketing expense when the agent's logo / contact information is inscribed on the blade. (otherwise, "client gift" deductions are limited to ~$25 / person / year. Agents that give closing gifts spend far more than that, but aren't able to get the full deduction)

As a result, this very specific subset of Cutco contractors must provide an invoice of services and product to their client. They have an acute pain point in generating accurate, concise, attractive invoices, due to a number of factors:
- Cutco / Vector is a venerable company (started in the 1940s / 1980s respectively) which never intended to serve this vertical,
- ... and therefore normally only provide a sort of shipping-manifest document to customers. Confusingly, this manifest would often have superfluous lines, adding and subtracting the same item multiple times (e.g., 10 qty - 10 qty + 2 qty = 2 qty over 3 lines)
- Additionally, they do not provide any REST APIs for accessing order information programmatically.
- Contractors coordinate and bundle 3rd-party value-adding services (e.g., engraving) which must be included in the invoice.

The most common solution is to do it by hand: a tedious process taking 10-30 minutes per invoice, which adds an average of 60 hours of administrative overhead per year. As a solution to that problem, this project was a minimum-viable-product, earning income while active until 2016.
