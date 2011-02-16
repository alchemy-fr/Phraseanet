#------------------------------------------------------------------------------
# File:         XMP2.pm
#
# Description:  Additional XMP schema definitions
#
# Revisions:    10/12/2008 - P. Harvey Created
#
# References:   1) PLUS - http://ns.useplus.org/
#               2) PRISM - http://www.prismstandard.org/
#               3) http://www.portfoliofaq.com/pfaq/v7mappings.htm
#               5) http://creativecommons.org/technology/xmp
#                  --> changed to http://wiki.creativecommons.org/Companion_File_metadata_specification (2007/12/21)
#               6) http://www.optimasc.com/products/fileid/xmp-extensions.pdf
#               9) http://www.w3.org/TR/SVG11/
#------------------------------------------------------------------------------

package Image::ExifTool::XMP;

use strict;
use Image::ExifTool qw(:Utils);
use Image::ExifTool::XMP;

# XMP Dynamic Media schema properties (xmpDM)
%Image::ExifTool::XMP::xmpDM = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-xmpDM', 2 => 'Image' },
    NAMESPACE => 'xmpDM',
    NOTES => 'XMP Dynamic Media schema tags.',
    absPeakAudioFilePath=> { },
    album               => { },
    altTapeName         => { },
    altTimecode => {
        SubDirectory => { },
        Struct => 'Timecode',
    },
    altTimecodeTimeFormat   => { },
    altTimecodeTimeValue    => { },
    altTimecodeValue        => { Writable => 'integer' },
    artist              => { Avoid => 1, Groups => { 2 => 'Author' } },
    audioModDate        => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    audioSampleRate     => { Writable => 'integer' },
    audioSampleType     => { },
    audioChannelType    => { },
    audioCompressor     => { },
    beatSpliceParams => {
        SubDirectory => { },
        Struct => 'BeatSpliceStretch',
    },
    beatSpliceParamsUseFileBeatsMarker => { Writable => 'boolean' },
    beatSpliceParamsRiseInDecibel      => { Writable => 'real' },
    beatSpliceParamsRiseInTimeDuration => {
        SubDirectory => { },
        Struct => 'Time',
    },
    beatSpliceParamsRiseInTimeDurationScale => { Writable => 'rational' },
    beatSpliceParamsRiseInTimeDurationValue => { Writable => 'integer' },
    cameraAngle         => { },
    cameraLabel         => { },
    cameraModel         => { },
    cameraMove          => { },
    client              => { },
    comment             => { Name => 'DMComment' },
    composer        => { Groups => { 2 => 'Author' } },
    contributedMedia => {
        SubDirectory => { },
        Struct => 'Media',
        List => 'Bag',
    },
    contributedMediaDuration => {
        SubDirectory => { },
        Struct => 'Time',
    },
    contributedMediaDurationScale => { List => 1, Writable => 'rational' },
    contributedMediaDurationValue => { List => 1, Writable => 'integer' },
    contributedMediaPath         => { List => 1 },
    contributedMediaTrack        => { List => 1 },
    contributedMediaStartTime => {
        SubDirectory => { },
        Struct => 'Time',
    },
    contributedMediaStartTimeScale => { List => 1, Writable => 'rational' },
    contributedMediaStartTimeValue => { List => 1, Writable => 'integer' },
    contributedMediaManaged      => { List => 1, Writable => 'boolean' },
    contributedMediaWebStatement => { List => 1 },
    copyright       => { Avoid => 1, Groups => { 2 => 'Author' } }, # (deprecated)
    director            => { },
    directorPhotography => { },
    duration        => {
        SubDirectory => { },
        Struct => 'Time',
    },
    durationScale   => { Writable => 'rational' },
    durationValue   => { Writable => 'integer' },
    engineer        => { },
    fileDataRate    => { Writable => 'rational' },
    genre           => { },
    good                => { Writable => 'boolean' },
    instrument      => { },
    introTime => {
        SubDirectory => { },
        Struct => 'Time',
    },
    introTimeScale  => { Writable => 'rational' },
    introTimeValue  => { Writable => 'integer' },
    key             => { },
    logComment      => { },
    loop            => { Writable => 'boolean' },
    numberOfBeats   => { Writable => 'real' },
    markers => {
        SubDirectory => { },
        Struct => 'Marker',
        List => 'Seq',
    },
    markersComment      => { List => 1 },
    markersCuePointParams => {
        SubDirectory => { },
        Struct => 'CuePointParam',
        List => 'Seq',
    },
    markersCuePointParamsValue => { List => 1 },
    markersCuePointParamsKey   => { List => 1 },
    markersCuePointType => { List => 1 },
    markersDuration     => { List => 1 },
    markersLocation     => { List => 1 },
    markersName         => { List => 1 },
    markersProbability  => { List => 1, Writable => 'real' },
    markersSpeaker      => { List => 1 },
    markersStartTime    => { List => 1 },
    markersTarget       => { List => 1 },
    markersType         => { List => 1 },
    metadataModDate => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    outCue => {
        SubDirectory => { },
        Struct => 'Time',
    },
    outCueScale         => { Writable => 'rational' },
    outCueValue         => { Writable => 'integer' },
    projectName         => { },
    projectRef => {
        SubDirectory => { },
        Struct => 'ProjectLink',
    },
    projectRefType      => { },
    projectRefPath      => { },
    pullDown            => { },
    relativePeakAudioFilePath => { },
    relativeTimestamp => {
        SubDirectory => { },
        Struct => 'Time',
    },
    relativeTimestampScale  => { Writable => 'rational' },
    relativeTimestampValue  => { Writable => 'integer' },
    releaseDate         => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    resampleParams => {
        SubDirectory => { },
        Struct => 'ResampleStretch',
    },
    resampleParamsQuality   => { PrintConv => { Low => 'Low', Medium => 'Medium', High => 'High' } },
    scaleType           => { },
    scene               => { Avoid => 1 },
    shotDate            => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    shotDay             => { },
    shotLocation        => { },
    shotName            => { },
    shotNumber          => { },
    shotSize            => { },
    speakerPlacement    => { },
    startTimecode => {
        SubDirectory => { },
        Struct => 'Timecode',
    },
    startTimecodeTimeFormat => { },
    startTimecodeTimeValue  => { },
    startTimecodeValue      => { Writable => 'integer' },
    stretchMode     => { },
    takeNumber      => { Writable => 'integer' },
    tapeName        => { },
    tempo           => { Writable => 'real' },
    timeScaleParams => {
        SubDirectory => { },
        Struct => 'TimeScaleStretch',
    },
    timeScaleParamsFrameOverlappingPercentage => { Writable => 'real' },
    timeScaleParamsFrameSize => { Writable => 'real' },
    timeScaleParamsQuality   => { PrintConv => { Low => 'Low', Medium => 'Medium', High => 'High' } },
    timeSignature   => { },
    trackNumber     => { Writable => 'integer' },
    Tracks => {
        SubDirectory => { },
        Struct => 'Track',
        List => 'Bag',
    },
    TracksFrameRate => { List => 1 },
    TracksMarkers => {
        SubDirectory => { },
        Struct => 'Marker',
        List => 'Seq',
    },
    TracksMarkersComment => { List => 1 },
    TracksMarkersCuePointParams => {
        SubDirectory => { },
        Struct => 'CuePointParam',
        List => 1,
    },
    TracksMarkersCuePointParamsKey  => { List => 1 },
    TracksMarkersCuePointParamsValue=> { List => 1 },
    TracksMarkersCuePointType       => { List => 1 },
    TracksMarkersDuration           => { List => 1 },
    TracksMarkersLocation           => { List => 1 },
    TracksMarkersName               => { List => 1 },
    TracksMarkersProbability        => { List => 1, Writable => 'real' },
    TracksMarkersSpeaker            => { List => 1 },
    TracksMarkersStartTime          => { List => 1 },
    TracksMarkersTarget             => { List => 1 },
    TracksMarkersType               => { List => 1 },
    TracksTrackName     => { List => 1 },
    TracksTrackType     => { List => 1 },
    videoAlphaMode      => { },
    videoAlphaPremultipleColor => {
        SubDirectory => { },
        Struct => 'Colorant',
    },
    videoAlphaPremultipleColorSwatchName => { },
    videoAlphaPremultipleColorMode       => { },
    videoAlphaPremultipleColorType       => { },
    videoAlphaPremultipleColorCyan       => { Writable => 'real' },
    videoAlphaPremultipleColorMagenta    => { Writable => 'real' },
    videoAlphaPremultipleColorYellow     => { Writable => 'real' },
    videoAlphaPremultipleColorBlack      => { Writable => 'real' },
    videoAlphaPremultipleColorRed        => { Writable => 'integer' },
    videoAlphaPremultipleColorGreen      => { Writable => 'integer' },
    videoAlphaPremultipleColorBlue       => { Writable => 'integer' },
    videoAlphaPremultipleColorL          => { Writable => 'real' },
    videoAlphaPremultipleColorA          => { Writable => 'integer' },
    videoAlphaPremultipleColorB          => { Writable => 'integer' },
    videoAlphaUnityIsTransparent => { Writable => 'boolean' },
    videoColorSpace     => { },
    videoCompressor     => { },
    videoFieldOrder     => { },
    videoFrameRate      => { },
    videoFrameSize => {
        SubDirectory => { },
        Struct => 'Dimensions',
    },
    videoFrameSizeW     => { Writable => 'real' },
    videoFrameSizeH     => { Writable => 'real' },
    videoFrameSizeUnit  => { },
    videoModDate        => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    videoPixelAspectRatio => { Writable => 'rational' },
    videoPixelDepth     => { },
);

#------------------------------------------------------------------------------
# PLUS vocabulary conversions
my %plusVocab = (
    ValueConv => '$val =~ s{http://ns.useplus.org/ldf/vocab/}{}; $val',
    ValueConvInv => '"http://ns.useplus.org/ldf/vocab/$val"',
);

# PLUS License Data Format 1.2.0 (plus) (ref 1)
%Image::ExifTool::XMP::plus = (
    %xmpTableDefaults,
    GROUPS => { 0 => 'XMP', 1 => 'XMP-plus', 2 => 'Author' },
    NAMESPACE => 'plus',
    NOTES => q{
        PLUS License Data Format 1.2.0 schema tags.  Note that all
        controlled-vocabulary tags in this table (ie. tags with a fixed set of
        values) have raw values which begin with "http://ns.useplus.org/ldf/vocab/",
        but to reduce clutter this prefix has been removed from the values shown
        below.  (see L<http://ns.useplus.org/>)
    },
    Version  => { Name => 'PLUSVersion' },
    Licensee => {
        SubDirectory => { },
        Struct => 'Licensee',
        List => 'Seq',
    },
    LicenseeLicenseeID   => { List => 1, Name => 'LicenseeID' },
    LicenseeLicenseeName => { List => 1, Name => 'LicenseeName' },
    EndUser => {
        SubDirectory => { },
        Struct => 'EndUser',
        List => 'Seq',
    },
    EndUserEndUserID    => { List => 1, Name => 'EndUserID' },
    EndUserEndUserName  => { List => 1, Name => 'EndUserName' },
    Licensor => {
        SubDirectory => { },
        Struct => 'Licensor',
        List => 'Seq',
    },
    LicensorLicensorID              => { List => 1, Name => 'LicensorID' },
    LicensorLicensorName            => { List => 1, Name => 'LicensorName' },
    LicensorLicensorStreetAddress   => { List => 1, Name => 'LicensorStreetAddress' },
    LicensorLicensorExtendedAddress => { List => 1, Name => 'LicensorExtendedAddress' },
    LicensorLicensorCity            => { List => 1, Name => 'LicensorCity' },
    LicensorLicensorRegion          => { List => 1, Name => 'LicensorRegion' },
    LicensorLicensorPostalCode      => { List => 1, Name => 'LicensorPostalCode' },
    LicensorLicensorCountry         => { List => 1, Name => 'LicensorCountry' },
    LicensorLicensorTelephoneType1  => {
        Name => 'LicensorTelephoneType1',
        List => 1,
        %plusVocab,
        PrintConv => {
            'work'  => 'Work',
            'cell'  => 'Cell',
            'fax'   => 'FAX',
            'home'  => 'Home',
            'pager' => 'Pager',
        },
    },
    LicensorLicensorTelephone1      => { List => 1, Name => 'LicensorTelephone1' },
    LicensorLicensorTelephoneType2  => {
        Name => 'LicensorTelephoneType2',
        List => 1,
        %plusVocab,
        PrintConv => {
            'work'  => 'Work',
            'cell'  => 'Cell',
            'fax'   => 'FAX',
            'home'  => 'Home',
            'pager' => 'Pager',
        },
    },
    LicensorLicensorTelephone2      => { List => 1, Name => 'LicensorTelephone2' },
    LicensorLicensorEmail           => { List => 1, Name => 'LicensorEmail' },
    LicensorLicensorURL             => { List => 1, Name => 'LicensorURL' },
    LicensorNotes               => { Writable => 'lang-alt' },
    MediaSummaryCode            => { },
    LicenseStartDate            => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    LicenseEndDate              => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    MediaConstraints            => { Writable => 'lang-alt' },
    RegionConstraints           => { Writable => 'lang-alt' },
    ProductOrServiceConstraints => { Writable => 'lang-alt' },
    ImageFileConstraints => {
        List => 'Bag',
        %plusVocab,
        PrintConv => {
            'IF-MFN' => 'Maintain File Name',
            'IF-MID' => 'Maintain ID in File Name',
            'IF-MMD' => 'Maintain Metadata',
            'IF-MFT' => 'Maintain File Type',
        },
    },
    ImageAlterationConstraints => {
        List => 'Bag',
        %plusVocab,
        PrintConv => {
            'AL-CRP' => 'No Cropping',
            'AL-FLP' => 'No Flipping',
            'AL-RET' => 'No Retouching',
            'AL-CLR' => 'No Colorization',
            'AL-DCL' => 'No De-Colorization',
            'AL-MRG' => 'No Merging',
        },
    },
    ImageDuplicationConstraints => {
        %plusVocab,
        PrintConv => {
            'DP-NDC' => 'No Duplication Constraints',
            'DP-LIC' => 'Duplication Only as Necessary Under License',
            'DP-NOD' => 'No Duplication',
        },
    },
    ModelReleaseStatus => {
        %plusVocab,
        PrintConv => {
            'MR-NON' => 'None',
            'MR-NAP' => 'Not Applicable',
            'MR-UMR' => 'Unlimited Model Releases',
            'MR-LMR' => 'Limited or Incomplete Model Releases',
        },
    },
    ModelReleaseID      => { List => 'Bag' },
    MinorModelAgeDisclosure => {
        %plusVocab,
        PrintConv => {
            'AG-UNK' => 'Age Unknown',
            'AG-A25' => 'Age 25 or Over',
            'AG-A24' => 'Age 24',
            'AG-A23' => 'Age 23',
            'AG-A22' => 'Age 22',
            'AG-A21' => 'Age 21',
            'AG-A20' => 'Age 20',
            'AG-A19' => 'Age 19',
            'AG-A18' => 'Age 18',
            'AG-A17' => 'Age 17',
            'AG-A16' => 'Age 16',
            'AG-A15' => 'Age 15',
            'AG-U14' => 'Age 14 or Under',
        },
    },
    PropertyReleaseStatus => {
        %plusVocab,
        PrintConv => {
            'PR-NON' => 'None',
            'PR-NAP' => 'Not Applicable',
            'PR-UPR' => 'Unlimited Property Releases',
            'PR-LPR' => 'Limited or Incomplete Property Releases',
        },
    },
    PropertyReleaseID   => { List => 'Bag' },
    OtherConstraints    => { Writable => 'lang-alt' },
    CreditLineRequired => {
        %plusVocab,
        PrintConv => {
            'CR-NRQ' => 'Not Required',
            'CR-COI' => 'Credit on Image',
            'CR-CAI' => 'Credit Adjacent To Image',
            'CR-CCA' => 'Credit in Credits Area',
        },
    },
    AdultContentWarning => {
        %plusVocab,
        PrintConv => {
            'CW-NRQ' => 'Not Required',
            'CW-AWR' => 'Adult Content Warning Required',
            'CW-UNK' => 'Unknown',
        },
    },
    OtherLicenseRequirements    => { Writable => 'lang-alt' },
    TermsAndConditionsText      => { Writable => 'lang-alt' },
    TermsAndConditionsURL       => { },
    OtherConditions             => { Writable => 'lang-alt' },
    ImageType => {
        %plusVocab,
        PrintConv => {
            'TY-PHO' => 'Photographic Image',
            'TY-ILL' => 'Illustrated Image',
            'TY-MCI' => 'Multimedia or Composited Image',
            'TY-VID' => 'Video',
            'TY-OTR' => 'Other',
        },
    },
    LicensorImageID     => { },
    FileNameAsDelivered => { },
    ImageFileFormatAsDelivered => {
        %plusVocab,
        PrintConv => {
            'FF-JPG' => 'JPEG Interchange Formats (JPG, JIF, JFIF)',
            'FF-TIF' => 'Tagged Image File Format (TIFF)',
            'FF-GIF' => 'Graphics Interchange Format (GIF)',
            'FF-RAW' => 'Proprietary RAW Image Format',
            'FF-DNG' => 'Digital Negative (DNG)',
            'FF-EPS' => 'Encapsulated PostScript (EPS)',
            'FF-BMP' => 'Windows Bitmap (BMP)',
            'FF-PSD' => 'Photoshop Document (PSD)',
            'FF-PIC' => 'Macintosh Picture (PICT)',
            'FF-PNG' => 'Portable Network Graphics (PNG)',
            'FF-WMP' => 'Windows Media Photo (HD Photo)',
            'FF-OTR' => 'Other',
        },
    },
    ImageFileSizeAsDelivered => {
        %plusVocab,
        PrintConv => {
            'SZ-U01' => 'Up to 1 MB',
            'SZ-U10' => 'Up to 10 MB',
            'SZ-U30' => 'Up to 30 MB',
            'SZ-U50' => 'Up to 50 MB',
            'SZ-G50' => 'Greater than 50 MB',
        },
    },
    CopyrightStatus => {
        %plusVocab,
        PrintConv => {
            'CS-PRO' => 'Protected',
            'CS-PUB' => 'Public Domain',
            'CS-UNK' => 'Unknown',
        },
    },
    CopyrightRegistrationNumber => { },
    FirstPublicationDate        => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    CopyrightOwner => {
        SubDirectory => { },
        Struct => 'CopyrightOwner',
        List => 'Seq',
    },
    CopyrightOwnerCopyrightOwnerID   => { List => 1, Name => 'CopyrightOwnerID' },
    CopyrightOwnerCopyrightOwnerName => { List => 1, Name => 'CopyrightOwnerName' },
    CopyrightOwnerImageID            => { },
    ImageCreator => {
        SubDirectory => { },
        Struct => 'ImageCreator',
        List => 'Seq',
    },
    ImageCreatorImageCreatorID   => { List => 1, Name => 'ImageCreatorID' },
    ImageCreatorImageCreatorName => { List => 1, Name => 'ImageCreatorName' },
    ImageCreatorImageID          => { },
    ImageSupplier => {
        SubDirectory => { },
        Struct => 'ImageSupplier',
        List => 'Seq',
    },
    ImageSupplierImageSupplierID   => { List => 1, Name => 'ImageSupplierID' },
    ImageSupplierImageSupplierName => { List => 1, Name => 'ImageSupplierName' },
    ImageSupplierImageID    => { },
    LicenseeImageID         => { },
    LicenseeImageNotes      => { Writable => 'lang-alt' },
    OtherImageInfo          => { Writable => 'lang-alt' },
    LicenseID               => { },
    LicensorTransactionID   => { List => 'Bag' },
    LicenseeTransactionID   => { List => 'Bag' },
    LicenseeProjectReference=> { List => 'Bag' },
    LicenseTransactionDate  => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    Reuse => {
        %plusVocab,
        PrintConv => {
            'RE-REU' => 'Repeat Use',
            'RE-NAP' => 'Not Applicable',
        },
    },
    OtherLicenseDocuments   => { List => 'Bag' },
    OtherLicenseInfo        => { Writable => 'lang-alt' },
    # Note: these are Bag's of lang-alt lists -- a nested list tag!
    Custom1     => { List => 'Bag', Writable => 'lang-alt' },
    Custom2     => { List => 'Bag', Writable => 'lang-alt' },
    Custom3     => { List => 'Bag', Writable => 'lang-alt' },
    Custom4     => { List => 'Bag', Writable => 'lang-alt' },
    Custom5     => { List => 'Bag', Writable => 'lang-alt' },
    Custom6     => { List => 'Bag', Writable => 'lang-alt' },
    Custom7     => { List => 'Bag', Writable => 'lang-alt' },
    Custom8     => { List => 'Bag', Writable => 'lang-alt' },
    Custom9     => { List => 'Bag', Writable => 'lang-alt' },
    Custom10    => { List => 'Bag', Writable => 'lang-alt' },
);

#------------------------------------------------------------------------------
# PRISM
#
# NOTE: The "Avoid" flag is set for all PRISM tags

# my %obsolete = (
#     Notes => 'obsolete in 2.0',
#     ValueConvInv => sub {
#         my ($val, $self) = @_;
#         unless ($self->Options('IgnoreMinorErrors')) {
#             warn "Warning: [minor] Attempt to write obsolete tag\n";
#             return undef;
#         }
#         return $val;
#     }
# );

# Publishing Requirements for Industry Standard Metadata 2.1 (prism) (ref 2)
%Image::ExifTool::XMP::prism = (
    %xmpTableDefaults,
    GROUPS => { 0 => 'XMP', 1 => 'XMP-prism', 2 => 'Document' },
    NAMESPACE => 'prism',
    NOTES => q{
        Publishing Requirements for Industry Standard Metadata 2.1 schema tags. (see
        L<http://www.prismstandard.org/>)
    },
    aggregationType => { List => 'Bag' },
    alternateTitle  => { List => 'Bag' },
    byteCount       => { Writable => 'integer' },
    channel         => { List => 'Bag' },
    complianceProfile=>{ PrintConv => { three => 'Three' } },
    copyright       => { Groups => { 2 => 'Author' } },
    corporateEntity => { List => 'Bag' },
    coverDate       => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    coverDisplayDate=> { },
    creationDate    => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    dateRecieved    => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    distributor     => { },
    doi             => { Name => 'DOI', Description => 'Digital Object Identifier' },
    edition         => { },
    eIssn           => { },
    embargoDate     => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    endingPage      => { },
    event           => { List => 'Bag' },
    expirationDate  => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    genre           => { List => 'Bag' },
    hasAlternative  => { List => 'Bag' },
    hasCorrection   => { },
    hasPreviousVersion => { },
    hasTranslation  => { List => 'Bag' },
    industry        => { List => 'Bag' },
    isCorrectionOf  => { List => 'Bag' },
    issn            => { Name => 'ISSN' },
    issueIdentifier => { },
    issueName       => { },
    isTranslationOf => { },
    keyword         => { List => 'Bag' },
    killDate        => { %dateTimeInfo, Groups => { 2 => 'Time'} },
    location        => { List => 'Bag' },
    # metadataContainer => { },
    modificationDate=> { %dateTimeInfo, Groups => { 2 => 'Time'} },
    number          => { },
    object          => { List => 'Bag' },
    organization    => { List => 'Bag' },
    originPlatform  => {
        List => 'Bag',
        PrintConv => {
            email       => 'E-Mail',
            mobile      => 'Mobile',
            broadcast   => 'Broadcast',
            web         => 'Web',
           'print'      => 'Print',
            recordableMedia => 'Recordable Media',
            other       => 'Other',
        },
    },
    pageRange       => { List => 'Bag' },
    person          => { },
    publicationDate => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    publicationName => { },
    rightsAgent     => { },
    section         => { },
    startingPage    => { },
    subsection1     => { },
    subsection2     => { },
    subsection3     => { },
    subsection4     => { },
    teaser          => { List => 'Bag' },
    ticker          => { List => 'Bag' },
    timePeriod      => { },
    url             => { Name => 'URL', List => 'Bag' },
    versionIdentifier => { },
    volume          => { },
    wordCount       => { Writable => 'integer' },
    # new in PRISM 2.1
    isbn            => { Name => 'ISBN' },
# tags that existed in version 1.3
#    category        => { %obsolete, List => 'Bag' },
#    hasFormat       => { %obsolete, List => 'Bag' },
#    hasPart         => { %obsolete, List => 'Bag' },
#    isFormatOf      => { %obsolete, List => 'Bag' },
#    isPartOf        => { %obsolete },
#    isReferencedBy  => { %obsolete, List => 'Bag' },
#    isRequiredBy    => { %obsolete, List => 'Bag' },
#    isVersionOf     => { %obsolete },
#    objectTitle     => { %obsolete, List => 'Bag' },
#    receptionDate   => { %obsolete },
#    references      => { %obsolete, List => 'Bag' },
#    requires        => { %obsolete, List => 'Bag' },
# tags in older versions
#    page
#    contentLength
#    creationTime
#    expirationTime
#    hasVersion
#    isAlternativeFor
#    isBasedOn
#    isBasisFor
#    modificationTime
#    publicationTime
#    receptionTime
#    releaseTime
);

# PRISM Rights Language 2.1 schema (prl) (ref 2)
%Image::ExifTool::XMP::prl = (
    %xmpTableDefaults,
    GROUPS => { 0 => 'XMP', 1 => 'XMP-prl', 2 => 'Document' },
    NAMESPACE => 'prl',
    NOTES => q{
        PRISM Rights Language 2.1 schema tags.  (see
        L<http://www.prismstandard.org/>)
    },
    geography       => { List => 'Bag' },
    industry        => { List => 'Bag' },
    usage           => { List => 'Bag' },
);

# PRISM Usage Rights 2.1 schema (prismusagerights) (ref 2)
%Image::ExifTool::XMP::pur = (
    %xmpTableDefaults,
    GROUPS => { 0 => 'XMP', 1 => 'XMP-pur', 2 => 'Document' },
    NAMESPACE => 'pur',
    NOTES => q{
        Prism Usage Rights 2.1 schema tags.  (see L<http://www.prismstandard.org/>)
    },
    adultContentWarning => { List => 'Bag' },
    agreement           => { List => 'Bag' },
    copyright           => { Writable => 'lang-alt', Groups => { 2 => 'Author' } },
    creditLine          => { List => 'Bag' },
    embargoDate         => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    exclusivityEndDate  => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    expirationDate      => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    imageSizeRestriction=> { },
    optionEndDate       => { List => 'Bag', %dateTimeInfo, Groups => { 2 => 'Time'} },
    permissions         => { List => 'Bag' },
    restrictions        => { List => 'Bag' },
    reuseProhibited     => { Writable => 'boolean' },
    rightsAgent         => { },
    rightsOwner         => { },
    usageFee            => { List => 'Bag' },
);

# DICOM schema properties (DICOM) (ref PH, written by CS3)
%Image::ExifTool::XMP::DICOM = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-DICOM', 2 => 'Image' },
    NAMESPACE => 'DICOM',
    NOTES => 'DICOM schema tags.',
    # change some tag names to correspond with DICOM tags
    PatientName             => { },
    PatientID               => { },
    PatientSex              => { },
    PatientDOB              => {
        Name => 'PatientBirthDate',
        Groups => { 2 => 'Time' },
        %dateTimeInfo,
    },
    StudyID                 => { },
    StudyPhysician          => { },
    StudyDateTime           => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    StudyDescription        => { },
    SeriesNumber            => { },
    SeriesModality          => { },
    SeriesDateTime          => { Groups => { 2 => 'Time' }, %dateTimeInfo },
    SeriesDescription       => { },
    EquipmentInstitution    => { },
    EquipmentManufacturer   => { },
);

# PixelLive schema properties (PixelLive) (ref 3)
%Image::ExifTool::XMP::PixelLive = (
    GROUPS => { 1 => 'XMP-PixelLive', 2 => 'Image' },
    NAMESPACE => 'PixelLive',
    WRITE_PROC => \&WriteXMP,
    NOTES => q{
        PixelLive schema tags.  These tags are not writable becase they are very
        uncommon and I haven't been able to locate a reference which gives the
        namespace URI.
    },
    AUTHOR    => { Name => 'Author',   Avoid => 1, Groups => { 2 => 'Author' } },
    COMMENTS  => { Name => 'Comments', Avoid => 1 },
    COPYRIGHT => { Name => 'Copyright',Avoid => 1, Groups => { 2 => 'Author' } },
    DATE      => { Name => 'Date',     Avoid => 1, Groups => { 2 => 'Time' } },
    GENRE     => { Name => 'Genre',    Avoid => 1 },
    TITLE     => { Name => 'Title',    Avoid => 1 },
);

# ACDSee schema (acdsee) (ref PH)
%Image::ExifTool::XMP::acdsee = (
    %xmpTableDefaults,
    GROUPS => { 0 => 'XMP', 1 => 'XMP-acdsee', 2 => 'Image' },
    NAMESPACE => 'acdsee',
    NOTES => q{
        ACD Systems ACDSee schema tags.  To software developers: Re-inventing your
        own private tags instead of using the equivalent tags in standard XMP
        namespaces defeats one of the most valuable features of metadata -- the
        ability to transfer information.  Your applications mumble to themselves
        instead of speaking out for the rest of the world to hear.
    },
    author     => { Avoid => 1, Groups => { 2 => 'Author' } },
    caption    => { Avoid => 1 },
    categories => { Avoid => 1 },
    datetime   => { Avoid => 1, Groups => { 2 => 'Time' }, %dateTimeInfo },
    keywords   => { Avoid => 1, List => 'Bag' },
    notes      => { Avoid => 1 },
    rating     => { Avoid => 1, Writable => 'real' }, # integer?
    tagged     => { Avoid => 1, Writable => 'boolean' },
    rpp => {
        Name => 'RPP',
        Writable => 'lang-alt',
        Notes => 'raw processing settings in XML format',
        Binary => 1,
    },
);

# Picture Licensing Universal System schema properties (xmpPLUS)
%Image::ExifTool::XMP::xmpPLUS = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-xmpPLUS', 2 => 'Author' },
    NAMESPACE => 'xmpPLUS',
    NOTES => 'XMP Picture Licensing Universal System (PLUS) schema tags.',
    CreditLineReq   => { Writable => 'boolean' },
    ReuseAllowed    => { Writable => 'boolean' },
);

# Creative Commons schema properties (cc) (ref 5)
%Image::ExifTool::XMP::cc = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-cc', 2 => 'Author' },
    NAMESPACE => 'cc',
    NOTES => q{
        Creative Commons schema tags.  (see
        L<http://creativecommons.org/technology/xmp>)
    },
    license => { },
    morePermissions => { },
    attributionName => { },
    attributionURL  => { },
);

# Description Explorer schema properties (dex) (ref 6)
%Image::ExifTool::XMP::dex = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-dex', 2 => 'Image' },
    NAMESPACE => 'dex',
    NOTES => q{
        Description Explorer schema tags.  These tags are not very common.  The
        Source and Rating tags are avoided when writing due to name conflicts with
        other XMP tags.  (see L<http://www.optimasc.com/products/fileid/>)
    },
    crc32       => { Name => 'CRC32', Writable => 'integer' },
    source      => { Avoid => 1 },
    shortdescription => {
        Name => 'ShortDescription',
        Writable => 'lang-alt',
    },
    licensetype => {
        Name => 'LicenseType',
        PrintConv => {
            unknown        => 'Unknown',
            shareware      => 'Shareware',
            freeware       => 'Freeware',
            adware         => 'Adware',
            demo           => 'Demo',
            commercial     => 'Commercial',
           'public domain' => 'Public Domain',
           'open source'   => 'Open Source',
        },
    },
    revision    => { },
    rating      => { Avoid => 1 },
    os          => { Name => 'OS', Writable => 'integer' },
    ffid        => { Name => 'FFID' },
);

# iView MediaPro schema properties (mediapro) (ref PH)
%Image::ExifTool::XMP::MediaPro = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-mediapro', 2 => 'Image' },
    NAMESPACE => 'mediapro',
    NOTES => 'iView MediaPro schema tags.',
    Event       => { },
    Location    => {
        Avoid => 1,
        Groups => { 2 => 'Location' },
        Notes => 'avoided due to conflict with XMP-iptcCore:Location',
    },
    Status      => { },
    People      => { List => 'Bag' },
    UserFields  => { List => 'Bag' },
    CatalogSets => { List => 'Bag' },
);

# DigiKam schema tags (ref PH)
%Image::ExifTool::XMP::digiKam = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-digiKam', 2 => 'Image' },
    NAMESPACE => 'digiKam',
    NOTES => 'DigiKam schema tags.',
    CaptionsAuthorNames    => { Writable => 'lang-alt' },
    CaptionsDateTimeStamps => { Writable => 'lang-alt' },
    TagsList               => { List => 'Seq' },
);

# SWF schema tags (ref PH)
%Image::ExifTool::XMP::swf = (
    %xmpTableDefaults,
    GROUPS => { 1 => 'XMP-swf', 2 => 'Image' },
    NAMESPACE => 'swf',
    NOTES => 'Adobe SWF schema tags.',
    type         => { Avoid => 1 },
    bgalpha      => { Name => 'BackgroundAlpha', Writable => 'integer' },
    forwardlock  => { Name => 'ForwardLock',     Writable => 'boolean' },
    maxstorage   => { Name => 'MaxStorage',      Writable => 'integer' }, # (CS5)
);

# SVG schema properties (ref 9)
%Image::ExifTool::XMP::SVG = (
    GROUPS => { 0 => 'SVG', 1 => 'SVG', 2 => 'Image' },
    NAMESPACE => 'svg',
    LANG_INFO => \&GetLangInfo,
    NOTES => q{
        SVG (Scalable Vector Graphics) image tags.  By default, only the top-level
        SVG and Metadata tags are extracted from these images, but all graphics tags
        may be extracted by setting the Unknown option to 2 (-U on the command
        line).  The SVG tags are not part of XMP as such, but are included with the
        XMP module for convenience.  (see L<http://www.w3.org/TR/SVG11/>)
    },
    version    => 'SVGVersion',
    id         => 'ID',
    metadataId => 'MetadataID',
    width      => 'ImageWidth',
    height     => 'ImageHeight',
);

# table to add tags in other namespaces
%Image::ExifTool::XMP::otherSVG = (
    GROUPS => { 0 => 'SVG', 2 => 'Unknown' },
    LANG_INFO => \&GetLangInfo,
);

# set "Avoid" flag for all PRISM tags
my ($table, $key);
foreach $table (
    \%Image::ExifTool::XMP::prism,
    \%Image::ExifTool::XMP::prl,
    \%Image::ExifTool::XMP::pur)
{
    foreach $key (TagTableKeys($table)) {
        $table->{$key}->{Avoid} = 1;
    }
}


1;  #end

__END__

=head1 NAME

Image::ExifTool::XMP - Additional XMP schema definitions

=head1 SYNOPSIS

This module is loaded automatically by Image::ExifTool when required.

=head1 DESCRIPTION

This file contains definitions for less common XMP schemas.

=head1 AUTHOR

Copyright 2003-2010, Phil Harvey (phil at owl.phy.queensu.ca)

This library is free software; you can redistribute it and/or modify it
under the same terms as Perl itself.

=head1 REFERENCES

=over 4

=item L<http://ns.useplus.org/>

=item L<http://www.prismstandard.org/>

=item L<http://www.portfoliofaq.com/pfaq/v7mappings.htm>

=item L<http://creativecommons.org/technology/xmp>

=item L<http://www.optimasc.com/products/fileid/xmp-extensions.pdf>

=item L<http://www.w3.org/TR/SVG11/>

=back

=head1 SEE ALSO

L<Image::ExifTool::TagNames/XMP Tags>,
L<Image::ExifTool(3pm)|Image::ExifTool>

=cut
