## 4. Property types

| Code | Arabic (default) | English |
| --- | --- | --- |
| APT | شقة | Apartment |
| VIL | فيلا | Villa |
| HSE | منزل | House |
| RIAD | رياض | Riad |
| KSB | قصبة | Kasbah |
| LND | أرض | Land / plot |
| FARM | ضيعة / أرض فلاحية | Farm / agricultural land |
| SHOP | محل تجاري | Shop / commercial unit |
| OFF | مكتب | Office |
| WAR | مستودع | Warehouse / industrial |
| HOT | فندق / دار ضيافة | Hotel / guesthouse |
| STU | استوديو | Studio |

Transaction types: **Sale (بيع)**, **Rent (كراء)**, and optionally **Seasonal rent (كراء موسمي)**.

## 5. Fields

### 5.1 Common fields (all types)

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| Reference | auto text | Yes | e.g. OZZ-2026-0001 |
| Property type | select | Yes | See section 4 |
| Transaction | select | Yes | Sale / Rent / Seasonal |
| Title AR / EN | text | Arabic: Yes. English: No |  |
| Description AR / EN | rich text | Arabic: Yes. English: No |  |
| Price (MAD) | number | Yes | Rent: per month (or per night if seasonal) |
| Price negotiable | boolean | No |  |
| Currency display | select | No | MAD default, EUR/USD display optional |
| City | select | Yes | Ouarzazate and surroundings |
| Address (private) | text | No | Not shown publicly |
| GPS location | lat/long or map pin | No | Map on listing page |
| Surface (m²) | number | Yes | Total area |
| Status | select | Yes | Draft, Published, Reserved, Sold/Rented, Archived |
| Featured | boolean | No |  |
| Images | file list | Yes (min. 1) | Cover + gallery, drag to reorder |
| Video URL | url | No | YouTube or upload |
| Legal title (Titre foncier) | select | No | Titré / Melkia / Non titré / Habous / Collectif |
| Owner (private) | select / create | No | Name in English required; Arabic name and phone optional. Internal only |
| Published at / Updated at | auto | – |  |

### 5.2 Built properties (Apartment, Villa, House, Riad, Kasbah, Studio)

| Field | Type | Notes |
| --- | --- | --- |
| Built area (m²) | number |  |
| Bedrooms | number |  |
| Bathrooms | number |  |
| Living rooms / Salons | number |  |
| Kitchens | number |  |
| Floor / Total floors | number | Mainly apartments |
| Year built | number |  |
| Condition | select | New, Good, To renovate, Under construction |
| Furnished | select | Yes / Semi / No (rent) |
| Parking / Garage | number |  |
| Amenities | multi-select | Pool, garden, terrace, elevator, AC, heating, fireplace, security, concierge, internet, solar water heater, balcony, cellar |
| Orientation / View | select | Mountain, street, garden, palm grove… |
| Monthly charges (syndic) | number | Apartments |
| Deposit (caution) | number | Rent |

**Kasbah / Riad extras:** historic or traditional style, courtyard (patio), number of patios, restored (yes/no), usable as guesthouse (yes/no), heritage classification.

### 5.3 Land and Farm

| Field | Type | Notes |
| --- | --- | --- |
| Land use / Zoning | select | Residential, commercial, agricultural, industrial, mixed |
| Land title | select | Titré / Melkia / other |
| Fenced | boolean |  |
| Road access | boolean |  |
| Utilities | multi-select | Water, electricity, sewage |
| Facade width (m) | number |  |
| Buildable (max floors / COS) | text | e.g. R+2 |
| Water source / well | boolean | Farm only |
| Trees / crops | text | Farm only |

### 5.4 Commercial (Shop, Office, Warehouse, Hotel)

| Field | Type | Notes |
| --- | --- | --- |
| Activity allowed | text |  |
| Street frontage (m) | number | Shops |
| Ceiling height (m) | number | Warehouses |
| Rooms / Offices | number |  |
| Hotel: rooms, stars, licensed | numbers / boolean | Hotel / guesthouse |
| Parking | number |  |
| Goodwill / Pas-de-porte | number | If applicable |

## 6. Inquiry (lead) fields

Full name, phone / WhatsApp, email (optional), message, related listing, language, date, status (New / Contacted / Closed), internal notes.

## 7. Non-functional requirements

- Responsive (mobile-first), RTL support for Arabic.
- Security: authentication, admin-only access, input validation, row-level or server-side permission checks, HTTPS.
- Performance: image compression and lazy loading; paginated lists.
- SEO: clean URLs, meta tags, sitemap updated on publish.
- Backups and data export (CSV).
- Hosting on current stack (the site is currently deployed on Vercel).

## 8. Suggested technical approach

- Backend and database: Supabase (PostgreSQL, Auth, Storage for images) with admin-only policies.
- Tables: properties, owners, property_images, property_attributes (type-specific fields), amenities, inquiries, admins, activity_log.
- Admin UI at /admin, protected route, connected to the existing frontend.

## 9. Deliverables

1. Admin panel (login, dashboard, listings CRUD, inquiries).
2. Database schema and migrations.
3. Connection of the public site to the live database.
4. Short admin user guide (Arabic).
5. Deployment and handover.

## 10. Planning (indicative)

| Phase | Content |
| --- | --- |
| 1 | Validation of this cahier des charges, property types and fields |
| 2 | Database and authentication |
| 3 | Listings CRUD, image upload, multilingual fields |
| 4 | Inquiries, dashboard, filters |
| 5 | Tests, training, deployment |

## Annex A. Database structure

The database has 11 tables. Listings sit at the center; everything else (images, amenities, owners, private data, inquiries) hangs off them.

### A.1 Relations

| From | To | Relation | Explanation |
| --- | --- | --- | --- |
| properties | property_types | many to one | Each listing has one type (villa, land, shop…) |
| properties | cities | many to one | Each listing is in one city |
| property_private | owners | many to one | An owner can have many listings; a listing has at most one owner |
| property_images | properties | many to one | A listing has many photos |
| property_private | properties | one to one | One private record per listing |
| property_amenities | properties + amenities | many to many | A listing has many amenities; an amenity is used by many listings |
| inquiries | properties | many to one | An inquiry can point to one listing (optional) |
| admins | auth users | one to one | Marks which login accounts can use the panel |

### A.2 Tables

#### properties (the main table)

*Purpose: one row per listing; holds everything that is public and common to all types.*

| Field | Type | Explanation |
| --- | --- | --- |
| id | UUID | Unique identifier |
| reference | text | Auto code shown to clients, e.g. OZZ-2026-0001 |
| slug | text, unique | URL part in Latin letters. Built at creation from the English title if there is one, otherwise from type + city + reference; replaced by the English title once it is specified. If the slug already exists, the reference is added at the end |
| title_ar | text, required | Listing title in Arabic (default language) |
| title_en | text, optional | Listing title in English |
| description_ar | rich text, required | Listing description in Arabic |
| description_en | rich text, optional | Listing description in English; the site shows the Arabic text when it is empty |
| type_code | link to property_types | Villa, Apartment, Land… |
| transaction | enum | Sale, Rent or Seasonal rent |
| status | enum | Draft, Published, Reserved, Sold/Rented, Archived |
| featured | yes/no | Shown first on the home page |
| price | decimal | In MAD (per month for rent, per night for seasonal) |
| price_negotiable | yes/no | Displays "negotiable" |
| city_id | links | Location |
| latitude, longitude | decimal | Map pin |
| surface_m2 | decimal | Total area |
| built_area_m2 | decimal | Built area (empty for land) |
| bedrooms, bathrooms | integer | Filled for built properties; used for search filters |
| legal_title | enum | Titré, Melkia, Non titré, Habous, Collectif |
| video_url | text | YouTube or other link |
| attributes | JSON | All type-specific fields (see A.3) |
| published_at, created_at, updated_at | date/time | Filled automatically (AND BY) |

#### property_private

*Purpose: confidential data about the listing, never visible on the public site.*

| Field | Type | Explanation |
| --- | --- | --- |
| property_id | link, unique | The listing it belongs to |
| owner_id | link to owners, optional | The owner of the property |
| address | text | Exact address |
| notes | text | Internal notes |

Kept separate from properties because database security works per row, not per column: anything in properties could be read by visitors.

#### owners

*Purpose: the people who own the properties the agency sells or rents. One owner can have several listings.*

| Field | Type | Explanation |
| --- | --- | --- |
| id | UUID | Identifier |
| name_en | text, required | Owner name in English (Latin letters) |
| name_ar | text, optional | Owner name in Arabic |
| phone | text, optional | Contact number |

Admin only: visitors never see owners. The link to a listing is stored in property_private, so even the fact that a listing has an owner stays private.

#### property_images

*Purpose: the photo gallery.*

| Field | Type | Explanation |
| --- | --- | --- |
| id | UUID | Identifier |
| property_id | link | The listing |
| path | text | File location in storage |
| position | integer | Display order (drag and drop in the admin) |
| is_cover | yes/no | Main photo; only one per listing |
| alt_text | text | Description for SEO and accessibility |

#### property_types (reference)

*Purpose: list of property types, editable without code.*

| Field | Type | Explanation |
| --- | --- | --- |
| code | text | APT, VIL, HSE, RIAD, KSB, STU, LND, FARM, SHOP, OFF, WAR, HOT |
| group | enum | Built, Land or Commercial; decides which form fields show |
| label_ar, label_en | text | Names in each language |
| position | integer | Display order |

#### cities (reference)

*Purpose: location lists for the listing form and the site filters.*

| Table | Fields | Explanation |
| --- | --- | --- |
| cities | id, name_ar, name_en | Ouarzazate and surrounding towns |

#### amenities and property_amenities

*Purpose: features such as pool, garden, terrace, elevator, parking.*

| Table | Fields | Explanation |
| --- | --- | --- |
| amenities | id, code, label_ar, label_en | The list of available features |
| property_amenities | property_id, amenity_id | Link table: which listing has which feature |

#### inquiries

*Purpose: messages sent by visitors from the public site.*

| Field | Type | Explanation |
| --- | --- | --- |
| id | UUID | Identifier |
| property_id | link, optional | Listing the visitor asked about |
| full_name, phone, email | text | Visitor contact (email optional) |
| message | text | Visitor message |
| lang | enum | Language of the site when sent |
| status | enum | New, Contacted, Closed |
| admin_notes | text | Internal follow-up |
| created_at | date/time | When it was sent |

Anyone can create an inquiry; only the admin can read or change them.

#### admins

*Purpose: list of login accounts allowed in the admin panel.*

| Field | Type | Explanation |
| --- | --- | --- |
| user_id | link to login account | The account |
| full_name | text | Display name |
| created_at | date/time | When added |

#### activity_log (optional)

*Purpose: history of who changed what.*

| Field | Type | Explanation |
| --- | --- | --- |
| user_id | link | Who did it |
| action | text | Insert, update or delete |
| table_name, record_id | text | What was changed |
| changes | JSON | Before and after values |
| created_at | date/time | When |

### A.3 Type-specific fields (stored in properties.attributes)

| Group | Types | Fields stored |
| --- | --- | --- |
| Built | Apartment, Villa, House, Riad, Kasbah, Studio | floor, total floors, year built, condition, furnished, living rooms, kitchens, parking, view/orientation, monthly charges, deposit; Riad/Kasbah also: patios, restored, usable as guesthouse |
| Land | Land, Farm | zoning, fenced, road access, utilities, facade width, buildable height; Farm also: water source, crops |
| Commercial | Shop, Office, Warehouse, Hotel | allowed activity, street frontage, ceiling height, rooms/offices, parking, goodwill; Hotel also: rooms, stars, licensed |

These fields change by type, so they live in one flexible JSON column instead of one table per type. Fields you filter on every day (price, surface, bedrooms) are real columns in properties.