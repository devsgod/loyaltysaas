<?php

return [

    /*
     |--------------------------------------------------------------------------
     | General
     |--------------------------------------------------------------------------
     */
    "current_password_incorrect" => "The current password is incorrect.",

    /*
     |--------------------------------------------------------------------------
     | Email and notifications
     |--------------------------------------------------------------------------
     */
    "reset_password_mail_subject" => "Reset your password",
    "reset_password_mail_top" => "You are receiving this email because we received a password reset request for your account. Please click the button below to set a new password.",
    "reset_password_mail_cta" => "Reset password",
    "reset_password_mail_bottom" => "If you did not request a password reset, no further action is required. This link is valid for 24 hours.",

    /*
     |--------------------------------------------------------------------------
     | User flow
     |--------------------------------------------------------------------------
     */

    // New user registration
    "user_registration_subject" => "Thank you for trying :account_name!",
    "user_registration_body_top" => ":user_name,\n\nThanks for trying :account_name. Your :trial_days day trial has started.",
    "user_registration_cta" => "Set up a loyalty campaign",
    "user_registration_body_bottom" => "Take us for a spin, and please email us at :support_email if you experience any issues or have any questions about setting up your account.",

    // User trial ends in 3 days
    "user_trial_ends_in_3_days_subject" => "Your trial ends in 3 days",
    "user_trial_ends_in_3_days_body_top" => ":user_name,\n\nThanks for trying :account_name. This is a kind reminder your trial expires in 3 days. When your trial expires, all data will be deleted permanently.",
    "user_trial_ends_in_3_days_cta" => "Upgrade account",
    "user_trial_ends_in_3_days_body_bottom" => "Regardless of your choice, we want to say thank you for trying :account_name. We know it's an investment of your time, and we appreciate you giving us a chance.",

    // User trial ends tomorrow
    "user_trial_ends_tomorrow_subject" => "Your trial ends tomorrow",
    "user_trial_ends_tomorrow_body_top" => "This is a kind reminder your :account_name account expires tomorrow. When your trial expires, all data will be deleted permanently. This can not be undone.",
    "user_trial_ends_tomorrow_cta" => "Upgrade account",
    "user_trial_ends_tomorrow_body_bottom" => "Regardless of your choice, we want to say thank you for trying :account_name. We know it's an investment of your time, and we appreciate you giving us a chance.",

    // User trial has ended
    "user_trial_has_ended_subject" => "Your trial has ended",
    "user_trial_has_ended_body_top" => "Thanks for trying :account_name. Your account and all associated data has been deleted. But no worries, you're always welcome to create a new account!",
    "user_trial_has_ended_cta" => "Create a new account",
    "user_trial_has_ended_body_bottom" => "Again, thank you for trying :account_name. We know it's an investment of your time, and we appreciate you gave us a chance.",

    // User account expired yesterday
    "user_account_expired_yesterday_subject" => "Your account has expired",
    "user_account_expired_yesterday_body_top" => "Thanks for using :account_name. We'd love to keep you as a customer, but we respect your decision. Your account and all associated data will be deleted after :grace_period_days days. You can prevent this by purchasing a subscription.",
    "user_account_expired_yesterday_cta" => "Prevent account removal",
    "user_account_expired_yesterday_body_bottom" => "Regardless of your choice, we want to say thank you for using :account_name!",

    // User account has been deleted
    "user_account_deleted_subject" => "Your account has been deleted",
    "user_account_deleted_body_top" => "We're sorry to see you go. All the best and you're always welcome back!",
    "user_account_deleted_cta" => "Create a new account",
    "user_account_deleted_body_bottom" => "Thanks again for using :account_name.",

    // User subscription expired (not used currently)
    "user_subscription_expired_subject" => "Your subscription has expired",
    "user_subscription_expired_body_top" => "This is a kind reminder your :account_name account expires tomorrow. When your subscription expires, it will be converted to the free trial account. No data will be lost at that point.",
    "user_subscription_expired_cta" => "Renew subscription",
    "user_subscription_expired_body_bottom" => "Regardless of your choice, we want to say thank you for trying :account_name. We know it's an investment of your time, and we appreciate you giving us a chance.",
];
