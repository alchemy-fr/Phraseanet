use ExtUtils::MakeMaker;
use File::Spec;

my $ExifTool_pm  = File::Spec->catfile('lib', 'Image', 'ExifTool.pm');
my $ExifTool_pod = File::Spec->catfile('lib', 'Image', 'ExifTool.pod');

WriteMakefile(
    NAME          => 'Image::ExifTool',
    VERSION_FROM  => $ExifTool_pm,
    PREREQ_PM     => { },
    clean         => { FILES => 't/*.tmp' },
    EXE_FILES     => [ 'exiftool' ],
    ($] >= 5.005
        ? (ABSTRACT_FROM => $ExifTool_pod,
           AUTHOR        => 'Phil Harvey (phil@owl.phy.queensu.ca)')
        : ()),
    ((ExtUtils::MakeMaker->VERSION() gt '6.30') ?
        (LICENSE => 'perl') : ()),    
);
