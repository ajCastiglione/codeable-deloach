<?php get_header(); ?>


<?php

query_posts( array (
'post_type' => 'realtors',
'posts_per_page' => -1,
'meta_key' => 'last_name',
'orderby' => 'meta_value',
'order' => 'ASC'
) );

while ( have_posts() ) : the_post();?>

<div class="agent-block">

<div class="agent-first-block">
<a href="<?php the_field('realtor_profile_link'); ?>"><img src="<?php the_field('agent_photo'); ?>" width="160" height="160"></a>
</div>

<div class="agent-second-block">
<h3>
<?php if( get_field('first_name') ): ?>
<?php the_field('first_name'); ?>
<?php endif; ?> 
<?php if( get_field('last_name') ): ?>
<?php the_field('last_name'); ?>
<?php endif; ?>
</h3>
<p style="font-size: 14px;"><?php if( get_field('mobile_phone') ): ?>
Mobile: <a href="tel:<?php the_field('mobile_phone'); ?>"><?php the_field('mobile_phone'); ?></a>
<?php endif; ?><br />
<?php if( get_field('office_phone') ): ?>
Office: <a href="tel:<?php the_field('office_phone'); ?>"><?php the_field('office_phone'); ?></a></p>
<?php endif; ?>
</div>

<div class="agent-third-block">
<?php if( get_field('realtor_facebook-business') ): ?>
<a href="<?php the_field('realtor_facebook-business'); ?>" target="_blank"><i class="fa fa-facebook"></i></a>
<?php endif; ?>

<?php if( get_field('realtor_facebook_-_personal') ): ?>
<a href="<?php the_field('realtor_facebook_-_personal'); ?>" target="_blank"><i class="fab fa-facebook-square"></i></a>
<?php endif; ?>

<?php if( get_field('realtor_instagram') ): ?>
<a href="<?php the_field('realtor_instagram'); ?>" target="_blank"><i class="fab fa-instagram" style="padding-right: 4px;"></i></a>
<?php endif; ?>

<?php if( get_field('realtor_twitter') ): ?>
<a href="<?php the_field('realtor_twitter'); ?>" target="_blank"><i class="fab fa-twitter-square"></i></a>
<?php endif; ?>

<?php if( get_field('agent_pinterest') ): ?>
<a href="<?php the_field('agent_pinterest'); ?>" target="_blank"><i class="fab fa-pinterest-square" style="padding-right: 4px;"></i></a>
<?php endif; ?>

<?php if( get_field('realtor_youtube') ): ?>
<a href="<?php the_field('realtor_youtube'); ?>" target="_blank"><i class="fab fa-youtube" style="padding-right: 4px;"></i></a>
<?php endif; ?>

<?php if( get_field('realtor_linkedin') ): ?>
<a href="<?php the_field('realtor_linkedin'); ?>" target="_blank"><i class="fab fa-linkedin" style="padding-right: 4px;"></i></a>
<?php endif; ?>

<?php if( get_field('zillow_link') ): ?>
<a href="<?php the_field('zillow_link'); ?>" target="_blank"><img src="https://deloachsir.com/wp-content/uploads/2019/08/Zillow-Realtor-Link.png"  alt="Zillow" /></a>
<?php endif; ?>

</div>

<div class="agent-fourth-block">
<?php if( get_field('realtor_email') ): ?>
<p style="font-size: 14px;"><a href="mailto:<?php the_field('realtor_email'); ?>"><i class="fa fa-envelope" aria-hidden="true" style="color:#567BBF"></i>   &nbsp;Email</a><br />
<?php endif; ?>

<?php if( get_field('realtor_profile_link') ): ?>
<a href="<?php the_field('realtor_profile_link'); ?>"><i class="fa fa-user" aria-hidden="true" style="color:#567BBF"></i> &nbsp;Agent Profile</a><br />
<?php endif; ?>

<?php if( get_field('agent_website') ): ?>
<a href="<?php the_field('agent_website'); ?>"><i class="fas fa-link" aria-hidden="true" style="color:#567BBF"></i> &nbsp;Agent Website</a></p>
<?php endif; ?>
</div>

</div>

<?php endwhile; // end of the loop. ?>

<?php get_footer(); ?>





