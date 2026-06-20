# Mobile Authentication API Documentation

This document describes the endpoints for the JWT-based mobile authentication system.

## 1. Overview & Authentication Flow

*   **Base URL**: `/api/v1`
*   **Protocol**: REST over HTTPS
*   **Security Guard**: JWT Bearer token authentication via the `api` guard.
*   **Standard Headers**:
    *   `Content-Type: application/json`
    *   `Accept: application/json`
    *   `Authorization: Bearer <TOKEN>` (required for protected endpoints)

---

## 2. API Endpoints

### 2.1 Login
*   **Route**: `POST /auth/login`
*   **Access**: Guest
*   **Request Body**:
    ```json
    {
      "login": "customer@example.com",
      "password": "password123"
    }
    ```
    *(Note: `login` can be the customer's email address or mobile number.)*

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
          "email": "customer@example.com",
          "mobile_number": "9876543210",
          "roles": ["customer"],
          "is_active": true
        },
        "customer": {
          "id": 5,
          "customer_number": "KT-001",
          "company_name": "Raj Garments",
          "gst_number": "27ABCDE1234F1Z5",
          "contact_person": "Rajesh Kumar",
          "mobile_number": "9876543210",
          "email": "customer@example.com",
          "customer_level": {
            "id": 2,
            "name": "Wholesale Distributor",
            "discount_percentage": 10
          },
          "credit": {
            "credit_limit": 500000.0,
            "outstanding_amount": 0.0,
            "available_credit": 500000.0,
            "overdue_amount": 0.0,
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

---

### 2.2 Get User Profile (Me)
*   **Route**: `GET /auth/me`
*   **Access**: Authenticated (`auth:api`)
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Authenticated user retrieved successfully.",
      "data": {
        "user": { ... },
        "customer": { ... },
        "navigation": { ... }
      }
    }
    ```

---

### 2.3 Token Refresh
*   **Route**: `POST /auth/refresh`
*   **Access**: Authenticated (`auth:api`)
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

---

### 2.4 Logout
*   **Route**: `POST /auth/logout`
*   **Access**: Authenticated (`auth:api`)
*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "Logout successful.",
      "data": []
    }
    ```

---

### 2.5 Change Password
*   **Route**: `POST /auth/change-password`
*   **Access**: Authenticated (`auth:api`)
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

---

### 2.6 Forgot Password Foundation
*   **Route**: `POST /auth/forgot-password`
*   **Access**: Guest
*   **Request Body**:
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

### 2.7 Request OTP
*   **Route**: `POST /auth/otp/send`
*   **Access**: Guest
*   **Request Body**:
    ```json
    {
      "login": "customer@example.com"
    }
    ```
    *(Note: `login` can be the customer's email address or mobile number.)*

*   **Success Response (200 OK)**:
    ```json
    {
      "success": true,
      "message": "OTP sent successfully. Any 6-digit code will pass.",
      "data": {
        "login": "customer@example.com"
      }
    }
    ```

*   **Error Responses**:
    *   **404 Not Found**: Account not found.
    *   **403 Forbidden**: Account inactive or restricted.
    *   **400 Bad Request**: Failed to send OTP.

---

### 2.8 Verify OTP and Login
*   **Route**: `POST /auth/otp/login`
*   **Access**: Guest
*   **Request Body**:
    ```json
    {
      "login": "customer@example.com",
      "otp": "123456"
    }
    ```
    *(Note: `otp` must be a 6-digit numeric string. In the current dummy implementation, any 6-digit code will pass.)*

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
          "email": "customer@example.com",
          "mobile_number": "9876543210",
          "roles": ["customer"],
          "is_active": true
        },
        "customer": {
          "id": 5,
          "customer_number": "KT-001",
          "company_name": "Raj Garments",
          "gst_number": "27ABCDE1234F1Z5",
          "contact_person": "Rajesh Kumar",
          "mobile_number": "9876543210",
          "email": "customer@example.com",
          "customer_level": {
            "id": 2,
            "name": "Wholesale Distributor",
            "discount_percentage": 10
          },
          "credit": {
            "credit_limit": 500000.0,
            "outstanding_amount": 0.0,
            "available_credit": 500000.0,
            "overdue_amount": 0.0,
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
    *(Note: The structure of `data` is identical to the standard Password Login response.)*

*   **Error Responses**:
    *   **404 Not Found**: Account not found.
    *   **403 Forbidden**: Account inactive or restricted.
    *   **401 Unauthorized**: Invalid OTP code. Please enter any 6 digits.

---

## 3. Error Responses & Status Codes

All errors return a consistent JSON response.

### 3.1 Invalid Credentials (401 Unauthorized)
```json
{
  "success": false,
  "message": "Invalid login credentials.",
  "errors": {}
}
```

### 3.2 Inactive / Restricted Account (403 Forbidden)
Returned if an admin tries to log in, if the customer profile is missing, or if the account is deactivated.
```json
{
  "success": false,
  "message": "Your account is inactive. Please contact support.",
  "errors": {}
}
```

### 3.3 Missing / Invalid Token (401 Unauthorized)
```json
{
  "success": false,
  "message": "Authentication token is missing.",
  "errors": {}
}
```

### 3.4 Expired Token (401 Unauthorized)
```json
{
  "success": false,
  "message": "Authentication token has expired.",
  "errors": {}
}
```

---

## 4. Flutter Integration Recommendations

### 4.1 Secure Token Storage
*   Do not store the JWT token in standard `SharedPreferences` as it stores data in plain text.
*   Use [flutter_secure_storage](https://pub.dev/packages/flutter_secure_storage) which uses Keychain on iOS and Keystore on Android to securely store the key-value pairs.

### 4.2 Restoring Session
On app launch, check if a stored token exists. If it does:
1. Fire a request to `GET /auth/me` containing the token in the `Authorization` header.
2. If it returns `200 OK`, restore the user session and navigate directly to the Dashboard.
3. If it returns `401` or `403`, clear local storage and show the login page.
