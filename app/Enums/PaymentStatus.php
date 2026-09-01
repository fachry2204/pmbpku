<?php
namespace App\Enums;
enum PaymentStatus:string { case Unpaid='unpaid'; case Pending='pending'; case Paid='paid'; case Expired='expired'; case Failed='failed'; case Refunded='refunded'; }
