<?php
namespace App\Enums;
enum SelectionStatus:string { case NotScheduled='not_scheduled'; case Scheduled='scheduled'; case AttendingTest='attending_test'; case Passed='passed'; case NotPassed='not_passed'; case Withdrawn='withdrawn'; }
