<?php
defined( 'ABSPATH' ) || exit();

class Coach_Notification {
	/**
	 * Coach daily notifications
	 */
	public static function coach_daily_notifications() {
		global $wpdb;
		$usermeta_table = $wpdb->prefix . 'usermeta';
		// get all coaches from the instructor plugin.
		$coaches        = ir_get_instructors();
		// loop through the coaches
		if ( ! empty( $coaches ) && count( $coaches ) ) {
			foreach ( $coaches as $coach ) {
				$coach_id    = $coach->id;
				// get all course list for the specific coach.
				$course_list = ir_get_instructor_complete_course_list( $coach_id );
				$org_id      = get_user_meta( $coach_id, 'org_id', true );
				// loop through the courses.
				if ( ! empty( $course_list ) && count( $course_list ) ) {
					$all_students = array();
					foreach ( $course_list as $course_id ) {
						// check if the course is not in trash.
						if ( 'trash' != get_post_status( $course_id ) ) {
							// get all assigned students for the specific course.
							$students_list = ir_get_users_with_course_access( $course_id, array( 'direct', 'group' ) );
							if ( empty( $students_list ) ) {
								continue;
							}

							//re-arrange the students and find out the students that are associated with the coach sponsor organization.
							$students_list = array_values( $students_list );
							$sql           = $wpdb->prepare( "SELECT user_id FROM {$usermeta_table} WHERE meta_key = 'org_id'  AND meta_value = %d", $org_id );
							$org_users     = $wpdb->get_col( $sql, 0 );

							// now, get only that students.
							if ( ! empty( $org_users ) ) {
								$students_list = array_intersect( $students_list, $org_users );
								$all_students = array_merge( $all_students, $students_list );
							}

						}
					}

					$unique_students_list = array_unique( $all_students );
					// as there are some students found, now get the assignments and docs.
					if ( ! empty( $unique_students_list ) ) {
						// get previousday submissions
						$previous_day    = date( 'Y-m-d', strtotime( '-1 day' ) );
						// get the assignments from the previous day that are only submitted by the students.
						$assignment_args = array(
							'post_type'      => learndash_get_post_type_slug( 'assignment' ),
							'post_status'    => 'publish',
							'posts_per_page' => -1,
							'author'         => implode( ",", $unique_students_list ),
							'date_query'     => array(
								'after'     => $previous_day . ' 00:00:00',
								'before'    => $previous_day . ' 23:59:59',
								'inclusive' => true
							)
						);
						$assignments     = get_posts( $assignment_args );
						// get the student docs from the previous day that are only submitted by the students.
						$google_docs_args = array(
							'post_type'      => 'student_google_doc',
							'post_status'    => 'pending',
							'posts_per_page' => -1,
							'author'         => implode( ",", $unique_students_list ),
							'date_query'     => array(
								'after'     => $previous_day . ' 00:00:00',
								'before'    => $previous_day . ' 23:59:59',
								'inclusive' => true
							),
						);

						$google_docs = get_posts( $google_docs_args );

						// now make the email content.
						if ( ! empty( $assignments ) || ! empty( $google_docs ) ) {
							// email subject.
							$subject = "New Assignments and Documents needs review";
							$message = "<div>";
							$message .= "<p style='margin-bottom:15px'>Hi, </p>";
							// email content for assignments.
							if ( ! empty( $assignments ) ) {
								// initial content for assignments content.
								$message .= "<p style='margin-bottom:15px'>Some writing assignments were submitted by your students.
								If this students are assigned to you, please visit <strong>Assignment Review</strong> tab in your Coach Dashboard, and access the student's document via link provided.
								After you have left your feedback please mark that document as <strong>Reviewed</strong> in the portal </p>";

								$message .= "<div style='margin-bottom: 40px;'>";
								$message .= "<table class='td' cellspacing='0' cellpadding='6' style='width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;' border='1'>";
								$message .= "<thead><tr>
								<th class='td' scope='col' style='text-align:center'>Assignment Name</th>
								<th class='td' scope='col' style='text-align:center'>Student Name</th>
								<th class='td' scope='col' style='text-align:center'>Lesson Name</th>
								<th class='td' scope='col' style='text-align:center'>Course Name</th></tr></thead>";
								$message .= "<tbody>";
								// fill up the table for the assignments.
								foreach ( $assignments as $assignment ) {
									$message          .= "<tr>";
									$assignment_title = $assignment->post_title;
									$student_name     = get_userdata( $assignment->post_author )->display_name;
									$lesson_name      = get_post_meta( $assignment->ID, 'lesson_title', true );
									$course_name      = get_the_title( get_post_meta( $assignment->ID, 'course_id', true ) );

									$message .= "<th class='td' scope='col' style='text-align:center'>" . $assignment_title . "</th>";
									$message .= "<th class='td' scope='col' style='text-align:center'>" . $student_name . "</th>";
									$message .= "<th class='td' scope='col' style='text-align:center'>" . $lesson_name . "</th>";
									$message .= "<th class='td' scope='col' style='text-align:center'>" . $course_name . "</th>";

									$message .= "</tr>";
								}
								$message .= "</tbody></table>";
								$message .= "</div>";

							}
							// email content for google docs.
							if ( ! empty( $google_docs ) ) {
								// initial content for docs.
								$message .= "<p style='margin-bottom:15px'>Some documents were submitted by your students.
								If this students are assigned to you, please visit <strong>Document Review</strong> tab in your Coach Dashboard, and access the student's document via link provided.
								After you have left your feedback please mark that document as <strong>Reviewed</strong> in the portal </p>";

								$message .= "<div style='margin-bottom: 40px;'>";
								$message .= "<table class='td' cellspacing='0' cellpadding='6' style='width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;' border='1'>";
								$message .= "<thead><tr>
								<th class='td' scope='col' style='text-align:center'>Document Name</th>
								<th class='td' scope='col' style='text-align:center'>Student Name</th>
								<th class='td' scope='col' style='text-align:center'>Course Name</th></tr></thead>";
								$message .= "<tbody>";
								// fill up the table for the assignments.
								foreach ( $google_docs as $google_doc ) {
									$message      .= "<tr>";
									$doc_title    = $google_doc->post_title;
									$student_name = get_userdata( $google_doc->post_author )->display_name;
									$course_name  = get_the_title( get_post_meta( $google_doc->ID, 'course_id', true ) );

									$message .= "<th class='td' scope='col' style='text-align:center'>" . $doc_title . "</th>";
									$message .= "<th class='td' scope='col' style='text-align:center'>" . $student_name . "</th>";
									$message .= "<th class='td' scope='col' style='text-align:center'>" . $course_name . "</th>";

									$message .= "</tr>";
								}
								$message .= "</tbody></table>";
								$message .= "</div>";
							}

							// get sponsor id
							$sponsor_id   = get_user_meta( $coach_id, "sponsor_id", true );
							$sponsor_name = "";
							if ( $sponsor_id ) {
								$sp           = get_term( $sponsor_id, 'payer' );
								$sponsor_name = $sp?->name ?? '';
							}

							// make email footer/thankyou content.
							$message .= "<p>Thank you,</p>";
							$message .= "<p>" . $sponsor_name . "</p>";
							$message .= "</div>";
							$coach_email = get_userdata( $coach_id )->user_email;
							$headers     = "Content-Type: text/html\r\n";
							// sent email to specific coach.
							wp_mail( $coach_email, $subject, $message, $headers );
						}
					}
				}
			}
		}
	}
}