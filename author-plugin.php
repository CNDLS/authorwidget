<?php
# CNDLS: Commented this out so it wouldn't display on every admin screen. (Rob)
#add_action('admin_menu', 'authors');

/* This function does not get used
function authors () {
	 wp_list_admin_users();
	 wp_list_nonadmin_users(); 
}
*/
# CNDLS: NOTE: This functions is similar to the wp_list_authors function in author-template.php.
# CNDLS: MODIFIED THE QUERY TO SORT BY LAST NAME
/**
 * List all the authors of the blog, with several options available.
 * optioncount (boolean) (false): Show the count in parenthesis next to the author's name.
 * exclude_admin (boolean) (true): Exclude the 'admin' user that is installed by default.
 * show_fullname (boolean) (false): Show their full names.
 * hide_empty (boolean) (true): Don't show authors without any posts.
 * feed (string) (''): If isn't empty, show links to author's feeds.
 * feed_image (string) (''): If isn't empty, use this image to link to feeds.
 * echo (boolean) (true): Set to false to return the output, instead of echoing.
 * @param array $args The argument array.
 * @return null|string The output, if echo is set to false.
 */

function cndls_list_authors($args = '') {
	global $wpdb, $blog_id;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'echo' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);

	$return = '';

	/*
	// TODO:  Move select to get_authors().
#	$authors = $wpdb->get_results("SELECT ID, user_nicename from $wpdb->users " . ($exclude_admin ? "WHERE user_login <> 'admin' " : '') . "ORDER BY display_name");
	
	# Display only members of a single blog
	$authors = $wpdb->get_results("SELECT ID, user_nicename from $wpdb->users, $wpdb->usermeta 
					WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level' AND 
					($wpdb->usermeta.user_id = $wpdb->users.id)" . ($exclude_admin ? "AND user_login <> 'admin' " : '') . 
					"ORDER BY cast(meta_value as unsigned) desc, display_name");
	*/			
	
	#CNDLS: Display only members of a single blog - sort by LAST NAME
	// $sub_query = "SELECT DISTINCT user_id from $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level'";
	$user_query = get_users('role=author');
    $author_list = array();
    // print_r($user_query);
    
    foreach ($user_query as $author) {
    	// print_r($author->ID);
    	array_push($author_list,$author->ID);
    }
    // print_r($author_list);

    $author_id_list = implode(',', $author_list);

    if (!empty($author_id_list)) { # check to make sure the array isn't empty
        $authors = $wpdb->get_results("SELECT user_id as ID, meta_value from $wpdb->usermeta
								WHERE $wpdb->usermeta.meta_key = 'last_name' AND $wpdb->usermeta.user_id IN ($author_id_list)
								ORDER BY meta_value");
		
        $author_count = array();
        foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
            $author_count[$row->post_author] = $row->count;
        }

        foreach ( (array) $authors as $author ) {
            $author = get_userdata( $author->ID );
            $posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
            $name = $author->display_name;
        
            if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
                $name = "$author->first_name $author->last_name";

            if ( !($posts == 0 && $hide_empty) )
                $return .= '<li>';
            if ( $posts == 0 ) {
                if ( !$hide_empty )
                    $link = $name;
            } else {
                $link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by %s"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

                if ( (! empty($feed_image)) || (! empty($feed)) ) {
                    $link .= ' ';
                    if (empty($feed_image))
                        $link .= '(';
                    $link .= '<a href="' . get_author_rss_link(0, $author->ID, $author->user_nicename) . '"';

                    if ( !empty($feed) ) {
                        $title = ' title="' . $feed . '"';
                        $alt = ' alt="' . $feed . '"';
                        $name = $feed;
                        $link .= $title;
                    }

                    $link .= '>';

                    if ( !empty($feed_image) )
                        $link .= "<img src=\"$feed_image\" border=\"0\"$alt$title" . ' />';
                    else
                        $link .= $name;

                    $link .= '</a>';

                    if ( empty($feed_image) )
                        $link .= ')';
                } //End of first empty(feed) if condition

                if ( $optioncount )
                    $link .= ' ('. $posts . ')';

            }//End of else part of post == 0 condition

            if ( !($posts == 0 && $hide_empty) )
                $return .= $link . '</li>';
        } //End of foreach loop
	
	} // End check for empty array

	if ( !$echo )
		return $return;
	echo $return;
	
} //End of function

/****** CNDLS: LIST THE ADMINS OF A BLOG SORTED BY LAST NAME *************/
function wp_list_admin_users($args = '') {
	global $wpdb, $blog_id;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'echo' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);

	$return = '';

	// $sub_query = "SELECT DISTINCT user_id from $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level'";
	$user_query = get_users('role=administrator');
    $admin_list = array();
    // print_r($user_query);
    
    foreach ($user_query as $admin) {
    	// print_r($author->ID);
    	array_push($admin_list,$admin->ID);
    }
    // print_r($author_list);

    $admin_id_list = implode(',', $admin_list);

    if (!empty($admin_id_list)) { # check to make sure the array isn't empty
        $authors = $wpdb->get_results("SELECT user_id as ID, meta_value from $wpdb->usermeta
                                    WHERE $wpdb->usermeta.meta_key = 'last_name' AND $wpdb->usermeta.user_id IN ($admin_id_list)
                                    ORDER BY meta_value");
        
        $author_count = array();
        foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
            $author_count[$row->post_author] = $row->count;
        }

        # fetch the ID of the owner from the database
        $owner['id'] = $wpdb->get_var("select id from $wpdb->users where user_email = '" . get_option('admin_email') . "'");

        # find the owner in the admin list
        $i = 0;
        foreach ( (array) $authors as $author ) {
            if ($owner['id'] == $author->ID) {
                $owner['key'] = $i;
            }
            $i++;
        }

        # reorder the admins array if necessary
        # 1) preserve the owner's object
        # 2) remove the owner from the admins array
        # 3) reset the array keys (array_merge trick)
        # 4) take the preserved owner object and prepend on the admins array
        if ($owner['key']) {
            $owner['object'] = $authors[$owner['key']];
            unset($authors[$owner['key']]);
            $authors = array_merge($authors);
            array_unshift($authors, $owner['object']);
        }

        foreach ( (array) $authors as $author ) {
            $author = get_userdata( $author->ID );
            $posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
            $name = $author->display_name;
        
            //Get the role for the authors
            $auth = $author -> ID;
            $auth_info = get_usermeta($auth, "wp_".$blog_id."_capabilities");
            # If user is a member of blog, only then does the $auth_info variable has value.
            # Hence, if check is this is a NON-EMPTY variable (Handles the error thrown if the $auth_info var is empty)
            if ($auth_info)
                $auth_role = key($auth_info);
        
            if ($auth_role == 'administrator'){
            if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
                $name = "$author->first_name $author->last_name";

            if ( !($posts == 0 && $hide_empty) )
                $return .= '<li>';
            if ( $posts == 0 ) {
                if ( !$hide_empty )
                    $link = $name;
            } else {
                $link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by $name %s"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

                if ( (! empty($feed_image)) || (! empty($feed)) ) {
                    $link .= ' ';
                    if (empty($feed_image))
                        $link .= '(';
                    $link .= '<a href="' . get_author_rss_link(0, $author->ID, $author->user_nicename) . '"';

                    if ( !empty($feed) ) {
                        $title = ' title="' . $feed . '"';
                        $alt = ' alt="' . $feed . '"';
                        $name = $feed;
                        $link .= $title;
                    }

                    $link .= '>';

                    if ( !empty($feed_image) )
                        $link .= "<img src=\"$feed_image\" border=\"0\"$alt$title" . ' />';
                    else
                        $link .= $name;

                    $link .= '</a>';

                    if ( empty($feed_image) )
                        $link .= ')';
                } //End of first empty(feed) if condition

                if ( $optioncount )
                    $link .= ' ('. $posts . ')';

            }//End of else part of post == 0 condition

            if ( !($posts == 0 && $hide_empty) )
                $return .= $link . '</li>';
        } //End of foreach loop
        } //Checking roles

    } // End check for empty array
	
	if ( !$echo )
		return $return;
	echo $return;
	
} //End of function

 /****** CNDLS: LIST THE NON-ADMINS OF A BLOG SORTED BY LAST NAME *************/
function wp_list_nonadmin_users($args = '') {
	global $wpdb, $blog_id;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'echo' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);

	$return = '';

	// $sub_query = "SELECT DISTINCT user_id from $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level'";
	$user_query = get_users('role=author');
    $author_list = array();
    // print_r($user_query);
    
    foreach ($user_query as $author) {
    	// print_r($author->ID);
    	array_push($author_list,$author->ID);
    }
    // print_r($author_list);

    $author_id_list = implode(',', $author_list);

    if (!empty($author_id_list)) { # check to make sure the array isn't empty

        $authors = $wpdb->get_results("SELECT user_id as ID, meta_value from $wpdb->usermeta
                                    WHERE $wpdb->usermeta.meta_key = 'last_name' AND $wpdb->usermeta.user_id IN ($author_id_list)
                                    ORDER BY meta_value");
        
        $author_count = array();
        foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
            $author_count[$row->post_author] = $row->count;
        }

        foreach ( (array) $authors as $author ) {
            $author = get_userdata( $author->ID );
            $posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
            $name = $author->display_name;
        
            //Get the role for the authors
            $auth = $author -> ID; 
            $auth_info = get_usermeta($auth, "wp_".$blog_id."_capabilities");
            # If user is a member of blog, only then does the $auth_info variable has value.
            # Hence, if check is this is a NON-EMPTY variable (Handles the error thrown if the $auth_info var is empty)
            if ($auth_info)
                $auth_role = key($auth_info);
        
            if ($auth_role != 'administrator'){
            if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
                $name = "$author->first_name $author->last_name";

            if ( !($posts == 0 && $hide_empty) )
                $return .= '<li>';
            if ( $posts == 0 ) {
                if ( !$hide_empty )
                    $link = $name;
            } else {
                $link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by $name (%s)"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

                if ( (! empty($feed_image)) || (! empty($feed)) ) {
                    $link .= ' ';
                    if (empty($feed_image))
                        $link .= '(';
                    $link .= '<a href="' . get_author_rss_link(0, $author->ID, $author->user_nicename) . '"';

                    if ( !empty($feed) ) {
                        $title = ' title="' . $feed . '"';
                        $alt = ' alt="' . $feed . '"';
                        $name = $feed;
                        $link .= $title;
                    }

                    $link .= '>';

                    if ( !empty($feed_image) )
                        $link .= "<img src=\"$feed_image\" border=\"0\"$alt$title" . ' />';
                    else
                        $link .= $name;

                    $link .= '</a>';

                    if ( empty($feed_image) )
                        $link .= ')';
                } //End of first empty(feed) if condition

                if ( $optioncount )
                    $link .= ' ('. $posts . ')';

            }//End of else part of post == 0 condition

            if ( !($posts == 0 && $hide_empty) )
                $return .= $link . '</li>';
        } //End of foreach loop
        } //Checking roles

    } // End check for empty array
	
	if ( !$echo )
		return $return;
	echo $return;
	
} //End of function

function author_image_tag($id) {
	# Check to see if an avatar has been stored in the DB
	if (get_usermeta($id, 'xprofile_avatar_v1')) {
		echo '<p><img src="' . get_usermeta($id, 'xprofile_avatar_v1') . '" class="gdc_avatar" /></p>';
	}
}

/** Added Feb 14, 2011 - this function will display ONLY authors in a blog
* AUTHORS = Users marked with the author role
**/
function cndls_list_role_authors($args = '') {
	global $wpdb, $blog_id;

	$defaults = array(
		'optioncount' => false, 'exclude_admin' => true,
		'show_fullname' => false, 'hide_empty' => true,
		'feed' => '', 'feed_image' => '', 'echo' => true
	);

	$r = wp_parse_args( $args, $defaults );
	extract($r, EXTR_SKIP);

	$return = '';
	# Select members of the blog whose role is set to authors (i.e.) wp_blog.id_user_level = 2
    // $sub_query = $wpdb->get_results("SELECT DISTINCT user_id from $wpdb->usermeta WHERE $wpdb->usermeta.meta_key = 'wp_".$wpdb->blogid."_user_level' AND $wpdb->usermeta.meta_value = 2");

    // print_r($sub_query);

    $user_query = get_users('role=author');
    $author_list = array();
    // print_r($user_query);
    
    foreach ($user_query as $author) {
    	// print_r($author->ID);
    	array_push($author_list,$author->ID);
    }
    // print_r($author_list);

    $author_id_list = implode(',', $author_list);

    if (!empty($author_id_list)) { # check to make sure the array isn't empty

        $authors = $wpdb->get_results("SELECT user_id as ID, meta_value from $wpdb->usermeta
                                    WHERE $wpdb->usermeta.meta_key = 'last_name' AND $wpdb->usermeta.user_id IN ($author_id_list)
                                    ORDER BY meta_value");
                    
        $author_count = array();
        foreach ((array) $wpdb->get_results("SELECT DISTINCT post_author, COUNT(ID) AS count FROM $wpdb->posts WHERE post_type = 'post' AND " . get_private_posts_cap_sql( 'post' ) . " GROUP BY post_author") as $row) {
            $author_count[$row->post_author] = $row->count;
        }

        foreach ( (array) $authors as $author ) {
            $author = get_userdata( $author->ID );
            $posts = (isset($author_count[$author->ID])) ? $author_count[$author->ID] : 0;
            $name = $author->display_name;
        
            //Get the role for the authors
            $auth = $author -> ID; 
            $auth_info = get_usermeta($auth, "wp_".$blog_id."_capabilities");
            # If user is a member of blog, only then does the $auth_info variable has value.
            # Hence, if check is this is a NON-EMPTY variable (Handles the error thrown if the $auth_info var is empty)
            if ($auth_info)
                $auth_role = key($auth_info);
            if ($auth_role != 'administrator'){
            if ( $show_fullname && ($author->first_name != '' && $author->last_name != '') )
                $name = "$author->first_name $author->last_name";

            if ( !($posts == 0 && $hide_empty) )
                $return .= '<li>';
            if ( $posts == 0 ) {
                if ( !$hide_empty )
                    $link = $name;
            } else {
                $link = '<a href="' . get_author_posts_url($author->ID, $author->user_nicename) . '" title="' . sprintf(__("Posts by $name (%s)"), attribute_escape($author->display_name)) . '">' . $name . '</a>';

                if ( (! empty($feed_image)) || (! empty($feed)) ) {
                    $link .= ' ';
                    if (empty($feed_image))
                        $link .= '(';
                    $link .= '<a href="' . get_author_rss_link(0, $author->ID, $author->user_nicename) . '"';

                    if ( !empty($feed) ) {
                        $title = ' title="' . $feed . '"';
                        $alt = ' alt="' . $feed . '"';
                        $name = $feed;
                        $link .= $title;
                    }

                    $link .= '>';

                    if ( !empty($feed_image) )
                        $link .= "<img src=\"$feed_image\" border=\"0\"$alt$title" . ' />';
                    else
                        $link .= $name;

                    $link .= '</a>';

                    if ( empty($feed_image) )
                        $link .= ')';
                } //End of first empty(feed) if condition

                if ( $optioncount )
                    $link .= ' ('. $posts . ')';

            }//End of else part of post == 0 condition

            if ( !($posts == 0 && $hide_empty) )
                $return .= $link . '</li>';
        } //End of foreach loop
        } //Checking roles

    } // End check for empty array
	
	if ( !$echo )
		return $return;
	echo $return;
	
} //End of function
?>