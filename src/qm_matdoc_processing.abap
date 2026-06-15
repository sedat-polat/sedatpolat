*&---------------------------------------------------------------------*
*& Report: QM_MATDOC_PROCESSING
*& Author: Sedat Polat
*& Purpose: Quality Management - Material Document Processing
*&          with Control Lot (PRUEFLOS) Filtering
*&---------------------------------------------------------------------*

REPORT qm_matdoc_processing.

*----------------------------------------------------------------------*
* Data Declarations
*----------------------------------------------------------------------*

TYPES: BEGIN OF ty_results,
         huident TYPE string,
         root_matnr TYPE matnr,
         root_charg TYPE charg_d,
         child_matnr TYPE matnr,
         child_charg TYPE charg_d,
       END OF ty_results,

       BEGIN OF ty_matdoc_second2,
         huident TYPE string,
         root_matnr TYPE matnr,
         root_charg TYPE charg_d,
         ersteldat TYPE datum,
         prueflos TYPE prueflos,
         charg TYPE charg_d,
         child_matnr TYPE matnr,
         child_charg TYPE charg_d,
         merknr TYPE merknr,
         kurztext TYPE ktext,
         toleranzun TYPE tole_un,
         toleranzob TYPE tole_ob,
         masseinhsw TYPE masseinhsw,
         vorglfnr TYPE vorglfnr,
         objnr TYPE objnr,
         art TYPE art_qc,
         verwmerkm TYPE verwmerkm,
         stellen TYPE stellen,
         measures TYPE char100,
       END OF ty_matdoc_second2,

       BEGIN OF ty_charg_group,
         charg TYPE charg_d,
         count TYPE i,
         min_ersteldat TYPE datum,
         min_prueflos TYPE prueflos,
       END OF ty_charg_group.

DATA: lt_results TYPE TABLE OF ty_results,
      lt_list_matdoc_second2 TYPE TABLE OF ty_matdoc_second2,
      lt_charg_group TYPE TABLE OF ty_charg_group,
      lt_delete_indices TYPE TABLE OF i,
      ls_matdoc TYPE ty_matdoc_second2,
      ls_charg_group TYPE ty_charg_group,
      lv_index TYPE i,
      lv_count TYPE i,
      is_import_werks TYPE werks_d VALUE '1000',
      iv_lang TYPE sprache VALUE 'T'.

*----------------------------------------------------------------------*
* Main Processing
*----------------------------------------------------------------------*

IF is_import_werks EQ '1000'.

  PERFORM select_matdoc_data.
  PERFORM filter_duplicate_prueflos.
  PERFORM delete_marked_records.

ENDIF.

*----------------------------------------------------------------------*
* FORM: select_matdoc_data
* Purpose: Select material document data with quality management joins
*----------------------------------------------------------------------*

FORM select_matdoc_data.

  SELECT
    mat_new~huident,
    mat_new~root_matnr,
    mat_new~root_charg,
    mat_new~child_matnr,
    mat_new~child_charg,
    qals~prueflos,
    qamv~merknr,
    qpmt~kurztext,
    qamv~toleranzun,
    qamv~toleranzob,
    qamv~masseinhsw,
    qamv~vorglfnr,
    qals~objnr,
    qals~art,
    qals~ersteldat,
    qals~charg,
    qamv~verwmerkm,
    qamv~stellen,
    @( VALUE char100( ) ) AS measures

    FROM @lt_results AS mat_new
    INNER JOIN qals ON qals~matnr = mat_new~child_matnr
                   AND qals~charg = mat_new~child_charg
    INNER JOIN qamv ON qamv~prueflos = qals~prueflos
    LEFT OUTER JOIN qpmt ON qpmt~mkmnr = qamv~verwmerkm
                        AND qpmt~sprache = @iv_lang
                        AND qpmt~zaehler = @is_import_werks

    WHERE NOT EXISTS (
      SELECT 1 FROM jest
       WHERE jest~objnr = qals~objnr
         AND jest~stat = 'I0224'
         AND jest~inact EQ @abap_false )

    ORDER BY mat_new~child_charg, qamv~merknr

    INTO TABLE @lt_list_matdoc_second2.

  IF sy-subrc EQ 0.
    WRITE: / 'Seçim başarılı. Toplam kayıt:', lines( lt_list_matdoc_second2 ).
  ELSE.
    WRITE: / 'Seçim başarısız!'.
  ENDIF.

ENDFORM.

*----------------------------------------------------------------------*
* FORM: filter_duplicate_prueflos
* Purpose: Filter records with multiple control lots (PRUEFLOS)
*          per charge (CHARG)
*
* Logic:
*  1. Group records by CHARG
*  2. If CHARG has multiple PRUEFLOS:
*     - Sort by ERSTELDAT (date created) and PRUEFLOS (ascending)
*     - Keep the oldest record (minimum date and PRUEFLOS)
*     - Mark others for deletion
*  3. Apply filter condition: ART IN ('04', '04BC')
*----------------------------------------------------------------------*

FORM filter_duplicate_prueflos.

  CLEAR: lt_charg_group, lt_delete_indices.

  SORT lt_list_matdoc_second2 BY charg ASCENDING ersteldat ASCENDING prueflos ASCENDING.

  LOOP AT lt_list_matdoc_second2 INTO ls_matdoc.

    lv_index = sy-tabix.

    " Kontrol et: Aynı CHARG'a sahip kaç kayıt var?
    CLEAR lv_count.
    LOOP AT lt_list_matdoc_second2 INTO ls_matdoc
      WHERE charg = ls_matdoc-charg.
      lv_count = lv_count + 1.
    ENDLOOP.

    " Eğer birden fazla PRUEFLOS varsa ve ART koşulu sağlanıyorsa
    IF lv_count GT 1 AND ls_matdoc-art IN ( '04', '04BC' ).

      " İlk kaydı (en eski ve en küçük PRUEFLOS) tut
      READ TABLE lt_list_matdoc_second2 INTO ls_matdoc
        WITH KEY charg = ls_matdoc-charg.

      IF sy-tabix EQ lv_index.
        " Bu ilk kayıt - tut
        CONTINUE.
      ELSE.
        " Diğer kayıtları sil
        APPEND lv_index TO lt_delete_indices.
      ENDIF.

    ENDIF.

  ENDLOOP.

ENDFORM.

*----------------------------------------------------------------------*
* FORM: delete_marked_records
* Purpose: Delete records marked for deletion from internal table
*----------------------------------------------------------------------*

FORM delete_marked_records.

  IF lt_delete_indices IS NOT INITIAL.

    SORT lt_delete_indices DESCENDING.

    LOOP AT lt_delete_indices INTO lv_index.
      DELETE lt_list_matdoc_second2 INDEX lv_index.
    ENDLOOP.

    WRITE: / 'Silinen kayıt sayısı:', lines( lt_delete_indices ).

  ELSE.
    WRITE: / 'Silinecek kayıt bulunamadı.'.
  ENDIF.

  WRITE: / 'Kalan kayıt sayısı:', lines( lt_list_matdoc_second2 ).

ENDFORM.

*&---------------------------------------------------------------------*
* End of Report
*&---------------------------------------------------------------------*
