# image-processing
Practice project: Basic edge and shape detection using PHP
Von Simmons, December 2018

This project had a lot of versions. Go to the following to see the most current:

shape/version1-1.php

Right off the bat, during this I realized I should've used a real versioning system. What I have is just amateurish.

The goal of this project was to create a PHP QR code reader. I had spent some time learning how to actually decode QR codes and was excited to build it. After thinking for awhile I thought that it would be better to show that I could replicate the entire process with PHP. Turns out PHP, or at least the way I did it, is not ideal for image processing. My code simply couldn't handle anything larger than 50x50 pixels without my laptop threatening to catch flames. As a result I've started learning more about Python's OpenCV and low-level programming which I find fascinating! Hopefully projects in that direction in the future.

Initially I was scanning my image with a bit of PHP that unpacked the file and read the pixel data into an array. During this process it would (what I called) "posterize" the pixel color to be either black or white. This way I could logically find objects in the image. Next I would look for a border with a threshold of white pixels on one side and black on the other. This would ensure (as well as I could figure) that the object wasn't noise. Adding contiguous points like this together would result in line segments. Finding overlapping line segments would reveal shapes. Then using some simple algebra, I would calculate extrema and centroids. Using the slopes of the line segments I figured I would be able to transform shapes into a standardized configuration, afterwhich the processed image could be sent to be decoded. Only, I didn't get that far.

It kept bugging me that there wasn't enough precision in my thresholds. So after some research I found out about kernels, matricies that mathematically process bits of an image. It allowed me to apply multiple filters to get a crisp, clean outline of shapes in the image. This way I would get a consistent result every time and I could be sure of my input before doing edge detection. Now I could apply a similar technique as before to find shapes more consistently.

This was the point when I wanted to start testing larger images. My first test was with a 600x600 pixel QR code. It ran incredibly slowly and I found that my edge detection wasn't performing as cleanly as it should. After trying to adjust some of the filters I figured the image just didn't have enough information to properly work. Trying a properly sized QR code (say, 1000x1000) would crash the browser. Thinking about it, running through that many pixels on the image was a lot, but then to run through that much data in arrays so many times was incredibly inefficient. And that led me to consider using Python instead to work through this.
