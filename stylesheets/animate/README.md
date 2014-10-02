### Animations

You can trigger animations just by using the `animated` class on any HTML element combined with the name of the animation you want to use. 

Use the following _Bourbon_ mixins to generate vendor prefixes on animations: 

      box:hover {
        // Animation shorthand works the same as the CSS3 animation shorthand
        @include animation(scale 1.0s ease-in, slide 2.0s ease);

        // The above outputs the same CSS as using independent, granular mixins.
        @include animation-name(scale, slide);
        @include animation-duration(2s);
        @include animation-timing-function(ease);
        @include animation-iteration-count(infinite);
      }
      
      // Animation Delay
      @include animation-delay(2s);

      // Animation direction
      @include animation-direction(alternate-reverse);

      // Animation Fill Mode
      @include animation-fill-mode(backwards);

      // Animation Play State
       @include animation-play-state(paused);