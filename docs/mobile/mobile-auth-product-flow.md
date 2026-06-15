# Mobile API Integration Guide for Flutter Developers

This guide details the integration endpoints, request/response schemas, security mechanisms, state management patterns, and selection math for integrating the Kannodia Textiles mobile application.

---

# Base API Information

All mobile communication is conducted over REST APIs. Ensure your API client implements the following settings:

*   **Production base URL**: `https://kannodia-textiles.com/api/v1`
*   **Local development example**: `http://127.0.0.1:8000/api/v1`
*   **Default Headers**:
    ```http
    Content-Type: application/json
    Accept: application/json
    ```
*   **Authentication**: JWT Bearer token passed in the header of protected endpoints:
    ```http
    Authorization: Bearer <JWT_TOKEN>
    ```

---

# Standard API Response Format

Every API request returns a standardized JSON envelope structure:

### Success Envelope
```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {},
  "meta": {}
}
```
*Note: The `meta` key is typically populated only on paginated listings to support infinite scrolls.*

### Standard Error Envelope
```json
{
  "success": false,
  "message": "Something went wrong.",
  "errors": {}
}
```

### Validation Error Envelope (HTTP 422)
```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "login": [
      "The login field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

---

# Authentication Flow

```
1. User enters email or mobile number and password on the login screen.
2. App sends POST request to `/auth/login`.
3. If successful, app stores the returned JWT token securely using secure storage.
4. App stores user/customer profile data locally in app memory state.
5. App routes the user to the default screen (from data.navigation.default_screen).
6. App attaches "Authorization: Bearer <TOKEN>" header to all subsequent requests.
7. If a request returns an HTTP 401 with an "Authentication token has expired" message, the app calls `/auth/refresh` once.
8. If refresh succeeds, the app replaces the stored token and retries the failed request.
9. If refresh fails (HTTP 401), the app clears storage, logs the user out, and returns to the login screen.
```

---

# Auth Endpoints

### 1. POST /auth/login
*   **Purpose**: Log in user via email or mobile number.
*   **Auth Required**: No (Guest)
*   **Endpoint**: `/auth/login`
*   **Headers**:
    ```http
    Content-Type: application/json
    Accept: application/json
    ```
*   **Request Body**:
    ```json
    {
      "login": "+91 98765 43210",
      "password": "password123"
    }
    ```
    *Alternative:*
    ```json
    {
      "login": "customer@example.com",
      "password": "password123"
    }
    ```
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Login successful.",
      "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "token_type": "bearer",
        "expires_in": 3600,
        "user": {
          "id": 12,
          "name": "Rajesh Kumar",
          "email": null,
          "mobile_number": "+91 98765 43210",
          "roles": ["customer"],
          "is_active": true
        },
        "customer": {
          "id": 5,
          "customer_number": "KT-001",
          "company_name": "Raj Garments",
          "gst_number": "27ABCDE1234F1Z5",
          "contact_person": "Rajesh Kumar",
          "mobile_number": "+91 98765 43210",
          "email": null,
          "customer_level": {
            "id": 2,
            "name": "Wholesale Distributor",
            "discount_percentage": 10
          },
          "credit": {
            "credit_limit": 500000.00,
            "outstanding_amount": 0.00,
            "available_credit": 500000.00,
            "overdue_amount": 0.00,
            "allow_credit_beyond_limit": false
          },
          "is_active": true
        },
        "navigation": {
          "default_screen": "dashboard",
          "can_access_products": true,
          "can_place_orders": true
        }
      }
    }
    ```

*   **Common Errors**:
    *   **HTTP 401 (Invalid Credentials)**:
        ```json
        {
          "success": false,
          "message": "Invalid login credentials.",
          "errors": {}
        }
        ```
    *   **HTTP 403 (Admin Access Blocked)**:
        ```json
        {
          "success": false,
          "message": "This account is not allowed to access the mobile app.",
          "errors": {}
        }
        ```
    *   **HTTP 403 (Missing Customer Profile)**:
        ```json
        {
          "success": false,
          "message": "Customer profile not found for this account.",
          "errors": {}
        }
        ```
    *   **HTTP 403 (Inactive Account)**:
        ```json
        {
          "success": false,
          "message": "Your account is inactive. Please contact support.",
          "errors": {}
        }
        ```

*   **Flutter Integration Notes**:
    *   Secure the JWT token using `flutter_secure_storage`.
    *   Check roles and active flags. Reject admin roles on UI level if necessary.
    *   Handle nullable email gracefully: if null, render "Not provided" or hide field in UI.

---

### 2. GET /auth/me
*   **Purpose**: Get current session profile. Used for auto-login verification on app start.
*   **Auth Required**: Yes
*   **Endpoint**: `/auth/me`
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Authenticated user retrieved successfully.",
      "data": {
        "user": {
          "id": 12,
          "name": "Rajesh Kumar",
          "email": null,
          "mobile_number": "+91 98765 43210",
          "roles": ["customer"],
          "is_active": true
        },
        "customer": {
          "id": 5,
          "customer_number": "KT-001",
          "company_name": "Raj Garments",
          "gst_number": "27ABCDE1234F1Z5",
          "contact_person": "Rajesh Kumar",
          "mobile_number": "+91 98765 43210",
          "email": null,
          "customer_level": {
            "id": 2,
            "name": "Wholesale Distributor",
            "discount_percentage": 10
          },
          "credit": {
            "credit_limit": 500000.00,
            "outstanding_amount": 0.00,
            "available_credit": 500000.00,
            "overdue_amount": 0.00,
            "allow_credit_beyond_limit": false
          },
          "is_active": true
        },
        "navigation": {
          "default_screen": "dashboard",
          "can_access_products": true,
          "can_place_orders": true
        }
      }
    }
    ```

*   **Errors**:
    *   **HTTP 401 (Expired Token)**:
        ```json
        {
          "success": false,
          "message": "Authentication token has expired.",
          "errors": {}
        }
        ```
    *   **HTTP 401 (Invalid/Missing Token)**:
        ```json
        {
          "success": false,
          "message": "Authentication token is missing.",
          "errors": {}
        }
        ```

---

### 3. POST /auth/refresh
*   **Purpose**: Obtain a fresh JWT token using the current token before/after expiration.
*   **Auth Required**: Yes
*   **Endpoint**: `/auth/refresh`
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Token refreshed successfully.",
      "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.newtoken...",
        "token_type": "bearer",
        "expires_in": 3600
      }
    }
    ```
*   **Errors**:
    *   **HTTP 401 (Invalid Refresh State)**:
        ```json
        {
          "success": false,
          "message": "Invalid authentication token.",
          "errors": {}
        }
        ```

---

### 4. POST /auth/logout
*   **Purpose**: Log out the user and invalidate the current token on the server.
*   **Auth Required**: Yes
*   **Endpoint**: `/auth/logout`
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Logout successful.",
      "data": []
    }
    ```

---

### 5. POST /auth/change-password
*   **Purpose**: Change the logged-in customer's password.
*   **Auth Required**: Yes
*   **Endpoint**: `/auth/change-password`
*   **Request Body**:
    ```json
    {
      "current_password": "password123",
      "password": "newpassword123",
      "password_confirmation": "newpassword123"
    }
    ```
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Password changed successfully. Please log in again.",
      "data": []
    }
    ```
*   **Errors**:
    *   **HTTP 422 (Incorrect current password)**:
        ```json
        {
          "success": false,
          "message": "Current password is incorrect.",
          "errors": {}
        }
        ```
    *   **HTTP 422 (Validation error)**:
        ```json
        {
          "success": false,
          "message": "Validation failed.",
          "errors": {
            "password": [
              "The password field must be at least 8 characters.",
              "The password confirmation does not match."
            ]
          }
        }
        ```

---

### 6. POST /auth/forgot-password
*   **Purpose**: Request a password reset. Returns a generic response to prevent account enumeration.
*   **Auth Required**: No (Guest)
*   **Endpoint**: `/auth/forgot-password`
*   **Request Body**:
    ```json
    {
      "login": "customer@example.com"
    }
    ```
    *Alternative:*
    ```json
    {
      "login": "9876543210"
    }
    ```
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "If the account exists, password reset instructions will be sent.",
      "data": null
    }
    ```

---

# Product Catalog Flow

```
1. App fetches nesting filter parameters from `/products/filters`.
2. App fetches lists of products from `/products` (attaching category, search, sort, or pagination states).
3. If user taps a product, app navigates to Product Details loading `/products/{slug-or-id}`.
4. Product Details displays details, images, units, swatches, and combination metadata.
5. If the product is variant-driven, the app resolves selected swatches to their matching combination details.
6. The app dynamically computes subtotals and estimated GST locally based on the selected unit and quantity.
7. Related items are loaded from `/products/{slug-or-id}/related`.
```

---

# Catalog Endpoints

### 1. GET /products
*   **Purpose**: Get paginated list of catalog items matching query parameters. Pricing is automatically tailored to the logged-in customer's level.
*   **Auth Required**: Yes
*   **Endpoint**: `/products`
*   **Query Parameters**:
    *   `search` (string, max 100) - Match title, description, or SKU.
    *   `category_id` (integer) - Filter by specific category ID.
    *   `category_slug` (string) - Filter by category slug.
    *   `availability` (`all`|`in_stock`|`low_stock`|`out_of_stock`) - Stock filter.
    *   `price_min` (numeric) - Minimum price range.
    *   `price_max` (numeric) - Maximum price range.
    *   `sort` (`newest`|`price_asc`|`price_desc`|`name_asc`|`availability`) - Sorting key.
    *   `per_page` (integer, max 50) - Items count per page (default: 12).
    *   `page` (integer) - Page number to retrieve.
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Products retrieved successfully.",
      "data": [
        {
          "id": 1,
          "slug": "premium-formal-cotton-shirt",
          "title": "Premium Formal Cotton Shirt",
          "sku": "TS-0012",
          "product_code": "KT-P-0001",
          "brand": "Kannodia Premium Apparel",
          "primary_image_url": "https://kannodia-textiles.com/storage/products/shirt.jpg",
          "categories": [
            {
              "id": 10,
              "name": "Shirts",
              "path": "Men’s Wear > Shirts"
            }
          ],
          "pricing": {
            "currency": "INR",
            "base_price": 500.00,
            "effective_base_price": 500.00,
            "discount_percentage": 30.00,
            "discount_source": "customer_level_default",
            "customer_price": 350.00,
            "unit_label": "Piece",
            "formatted_customer_price": "₹350.00"
          },
          "availability": {
            "available_quantity": 120,
            "status": "in_stock",
            "label": "In Stock",
            "is_purchasable": true
          },
          "units": {
            "base_unit": {
              "id": 1,
              "name": "Piece",
              "short_code": "pcs",
              "label": "Piece"
            },
            "available_units_count": 2
          },
          "minimum_order_quantity": 10,
          "is_active": true
        }
      ],
      "meta": {
        "current_page": 1,
        "per_page": 12,
        "total": 56,
        "last_page": 5,
        "from": 1,
        "to": 12
      }
    }
    ```

---

### 2. GET /products/filters
*   **Purpose**: Get categories hierarchy, sort modes, and availability filter metadata.
*   **Auth Required**: Yes
*   **Endpoint**: `/products/filters`
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Product filters retrieved successfully.",
      "data": {
        "categories": [
          {
            "id": 1,
            "name": "Men’s Wear",
            "slug": "mens-wear",
            "children": [
              {
                "id": 2,
                "name": "Shirts",
                "slug": "shirts",
                "children": []
              }
            ]
          }
        ],
        "availability": [
          { "value": "all", "label": "All" },
          { "value": "in_stock", "label": "In Stock" },
          { "value": "low_stock", "label": "Low Stock" },
          { "value": "out_of_stock", "label": "Out of Stock" }
        ],
        "sort": [
          { "value": "newest", "label": "Newest Arrivals" },
          { "value": "price_asc", "label": "Price: Low to High" },
          { "value": "price_desc", "label": "Price: High to Low" },
          { "value": "name_asc", "label": "Name: A to Z" }
        ],
        "price_range": {
          "min": 100,
          "max": 10000,
          "currency": "INR"
        }
      }
    }
    ```

---

### 3. GET /products/{slug-or-id}
*   **Purpose**: Get complete product catalog configuration details.
*   **Auth Required**: Yes
*   **Endpoint**: `/products/premium-formal-cotton-shirt` (or `/products/1`)
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Product retrieved successfully.",
      "data": {
        "id": 1,
        "slug": "premium-formal-cotton-shirt",
        "title": "Premium Formal Cotton Shirt",
        "sku": "TS-0012",
        "product_code": "KT-P-0001",
        "brand": "Kannodia Premium Apparel",
        "status": "active",
        "description": {
          "markdown": "Premium long-staple combed cotton fabric...",
          "html": "<p>Premium long-staple combed cotton fabric...</p>",
          "plain_text": "Premium long-staple combed cotton fabric..."
        },
        "media": [
          {
            "id": 1,
            "url": "https://kannodia-textiles.com/storage/products/shirt.jpg",
            "type": "image",
            "mime_type": "image/jpeg",
            "is_primary": true,
            "sort_order": 1,
            "alt_text": "Premium Formal Cotton Shirt"
          }
        ],
        "categories": [
          {
            "id": 10,
            "name": "Shirts",
            "slug": "shirts",
            "path": "Men’s Wear > Shirts"
          }
        ],
        "breadcrumb": [
          { "label": "Home", "type": "home" },
          { "label": "Products", "type": "products" },
          { "id": 5, "label": "Men’s Wear", "slug": "mens-wear", "type": "category" },
          { "id": 10, "label": "Shirts", "slug": "shirts", "type": "category" },
          { "label": "Premium Formal Cotton Shirt", "type": "product" }
        ],
        "pricing": {
          "currency": "INR",
          "base_price": 500.00,
          "effective_base_price": 500.00,
          "discount_percentage": 30.00,
          "discount_source": "customer_level_default",
          "customer_price": 350.00,
          "formatted_customer_price": "₹350.00"
        },
        "availability": {
          "available_quantity": 120,
          "status": "in_stock",
          "label": "In Stock",
          "is_purchasable": true
        },
        "units": [
          {
            "id": 1,
            "level": 1,
            "name": "Piece",
            "short_code": "pcs",
            "conversion_to_base": 1,
            "price": 350.00,
            "formatted_price": "₹350.00",
            "label": "Piece"
          },
          {
            "id": 2,
            "level": 2,
            "name": "Pack",
            "short_code": "pack",
            "conversion_to_base": 10,
            "price": 3500.00,
            "formatted_price": "₹3,500.00",
            "label": "Pack (10 Pcs)"
          }
        ],
        "variations": [
          {
            "id": 1,
            "name": "Size",
            "display_type": "text",
            "has_images": false,
            "values": [
              { "id": 1, "value": "S", "color_hex": null, "is_default": false, "media": [] },
              { "id": 2, "value": "M", "color_hex": null, "is_default": true, "media": [] }
            ]
          },
          {
            "id": 2,
            "name": "Color",
            "display_type": "color",
            "has_images": true,
            "values": [
              {
                "id": 5,
                "value": "Black",
                "color_hex": "#000000",
                "is_default": true,
                "media": [
                  {
                    "id": 7,
                    "url": "https://kannodia-textiles.com/storage/products/black-shirt.jpg",
                    "sort_order": 1
                  }
                ]
              }
            ]
          }
        ],
        "combinations": [
          {
            "id": 1,
            "label": "M / Black",
            "values": {
              "Size": "M",
              "Color": "Black"
            },
            "value_ids": [2, 5],
            "sku": "TS-0012-M-BLK",
            "price": {
              "effective_base_price": 500.00,
              "customer_price": 350.00,
              "formatted_customer_price": "₹350.00"
            },
            "availability": {
              "available_quantity": 12,
              "status": "in_stock",
              "label": "In Stock",
              "is_purchasable": true
            },
            "is_active": true
          }
        ],
        "purchase_defaults": {
          "selected_unit_id": 1,
          "quantity": 10,
          "minimum_order_quantity": 10,
          "gst_percentage": 12
        }
      }
    }
    ```

---

### 4. GET /products/{slug-or-id}/related
*   **Purpose**: Get related products sharing similar categories.
*   **Auth Required**: Yes
*   **Endpoint**: `/products/premium-formal-cotton-shirt/related?limit=4`
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Related products retrieved successfully.",
      "data": [
        {
          "id": 2,
          "slug": "executive-oxford-blue-shirt",
          "title": "Executive Oxford Blue Shirt",
          "sku": "TS-0013",
          "primary_image_url": "https://kannodia-textiles.com/storage/products/oxford.jpg",
          "pricing": {
            "currency": "INR",
            "customer_price": 420.00,
            "formatted_customer_price": "₹420.00",
            "unit_label": "Piece"
          },
          "availability": {
            "status": "in_stock",
            "label": "In Stock",
            "is_purchasable": true
          }
        }
      ]
    }
    ```

---

# Variation Selection Logic

If a product contains a non-empty `variations` array, it has multiple options (e.g. Size, Color) that determine its final pricing and availability. Use the following logic in Flutter to track selection states:

1. Render the option groups returned in `data.variations`. Option buttons or swatches should match the `display_type` parameter:
   * `text` -> Render as custom pill buttons.
   * `color` -> Render as rounded color swatch buttons displaying `color_hex`.
   * `image` -> Render as thumbnail selector buttons using `media.url`.
2. Keep track of the user's selected value ID for each variation group in a Map (e.g. `{ 'Size': 2, 'Color': 5 }`).
3. Convert selection values to a list of IDs (e.g. `[2, 5]`).
4. Find the matching combination in the `data.combinations` array:
   ```dart
   // Dart selection matcher helper
   Map<String, dynamic>? findMatchingCombination(
       List<Map<String, dynamic>> combinations, List<int> selectedValueIds) {
     return combinations.firstWhere(
       (combo) {
         final List<dynamic> comboValIds = combo['value_ids'];
         return comboValIds.length == selectedValueIds.length &&
             comboValIds.every((id) => selectedValueIds.contains(id));
       },
       orElse: () => null,
     );
   }
   ```
5. If a matching combination is found:
   * Update the UI price dynamically with `combination.price.formatted_customer_price`.
   * Update the stock badge and purchasability using the `combination.availability` metrics.
6. If no matching combination is resolved (or if the combination has `is_purchasable == false`):
   * Display "Combination Unavailable" on the details page.
   * Disable the "Add to Cart" button.

---

# Unit and Quantity Calculation

Wholesale orders support purchases in different conversion quantities (e.g. Pieces vs. Boxes vs. Packs).

### Local Calculations Formula

Once a unit is selected, calculate display estimates:

$$\text{Line Subtotal} = \text{selectedUnit.price} \times \text{quantity}$$

$$\text{Estimated GST Amount} = \text{Line Subtotal} \times \frac{\text{gst\_percentage}}{100}$$

$$\text{Estimated Total Cost} = \text{Line Subtotal} + \text{Estimated GST Amount}$$

*Example Implementation Case:*
*   **Selected Unit**: `Pack (10 Pcs)` with resolved unit price `₹3,500.00`
*   **Selected Quantity**: `2`
*   **Line Subtotal**: $3500 \times 2 = ₹7,000.00$
*   **GST Percentage**: `12%` ($7000 \times 0.12 = ₹840.00$)
*   **Estimated Total Cost**: $7000 + 840 = ₹7,840.00$

> [!WARNING]
> Keep the calculation labeled as an **Estimate** in the UI. Final prices are calculated and confirmed by the Cart/Checkout APIs.

---

# Product API Error Matrix

Use this matrix to write global interceptors/handling logic:

| Scenario | HTTP Code | API message | Flutter Action |
| :--- | :---: | :--- | :--- |
| **Missing JWT Token** | `401` | `Authentication token is missing.` | Prompt login / redirect user. |
| **Expired JWT Token** | `401` | `Authentication token has expired.` | Initiate automatic refresh token call. |
| **Invalid Signature** | `401` | `Invalid authentication token.` | Discard local tokens; route to login. |
| **Deactivated Account** | `403` | `Your account is inactive. Please contact support.` | Show error card/support link block. |
| **Product Not Found** | `404` | `Product not found.` | Render "Item does not exist" placeholder. |
| **Invalid Filters** | `422` | `Validation failed.` | Render field-level filter constraints. |
| **Server Failures** | `500` | `Could not retrieve products.` | Show generic "Retry connection" screen. |

---

# Suggested Flutter Structure

Organize the Flutter application directories to cleanly isolate API clients, models, and presentation states:

```text
lib/
├── core/
│   ├── network/
│   │   ├── api_client.dart          // Base Dio or HTTP client
│   │   ├── auth_interceptor.dart    // Handles automatic 401 token refresh retries
│   │   └── token_storage.dart       // Wraps flutter_secure_storage
│   ├── errors/
│   └── utils/
├── features/
│   ├── auth/
│   │   ├── data/
│   │   │   ├── auth_api.dart
│   │   │   ├── auth_repository.dart
│   │   │   └── models/
│   │   │       ├── auth_response.dart
│   │   │       ├── user_model.dart
│   │   │       └── customer_model.dart
│   │   └── presentation/
│   └── products/
│       ├── data/
│       │   ├── product_api.dart
│       │   ├── product_repository.dart
│       │   └── models/
│       │       ├── product_card.dart
│       │       ├── product_detail.dart
│       │       ├── product_unit.dart
│       │       ├── variation.dart
│       │       └── combination.dart
│       └── presentation/
```

---

# Token Handling

1.  **Secure Storage**: Never save raw tokens in `SharedPreferences`. Use `flutter_secure_storage`.
2.  **Interceptor Logic**: Implement a Dio Interceptor to handle `401` errors:
    *   Lock other outbound HTTP requests.
    *   Send a single `POST /auth/refresh` request.
    *   On success, overwrite the secure storage token, release lock, and retry the original request.
    *   On failure, wipe credentials, release lock, and push to login page.
3.  **Security boundaries**: Wipe tokens on logout or when the server rejects a refreshed token request.

---

# Loading, Empty, and Error UI Guidance

Ensure optimal UX by implementing standard states:

### 1. Login
*   **Loading state**: Replace "Log In" button text with a spinner. Disable interactions.
*   **Error rendering**: Highlight matching text fields in red if `422 Validation Error` returns fields mapping (e.g. `login`, `password`).

### 2. Product Catalog
*   **Skeleton indicators**: Render gray box layout loaders while `/products` is running.
*   **Infinite scrolls**: Monitor scroll controller and load the next page if `meta.current_page < meta.last_page`. Show a small loading indicator at the bottom.
*   **Empty filters**: Render a clean search icon with the text "No results match your selected filters."

### 3. Product Details
*   **Interactive swatches**: Disable swatches that would lead to an invalid variation combination.
*   **Visual cues**: Display out-of-stock variations in light gray with strikethroughs.

---

# Cart Scope Note

> [!NOTE]
> Order placing and server-side cart operations are outside the scope of this phase. Do not implement HTTP calls for cart additions. 
> 
> Instead, manage the shopping cart locally in the app database or state (e.g., using SQLite or Hive). The Product Detail endpoint provides all parameters required to prepare items locally.
>
> Expected payload for future Cart endpoints:
> ```json
> {
>   "product_id": 1,
>   "combination_id": 10,
>   "unit_id": 2,
>   "quantity": 3
> }
> ```
